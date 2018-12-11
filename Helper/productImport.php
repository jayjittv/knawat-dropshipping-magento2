<?php
namespace Knawat\Dropshipping\Helper;

use Knawat\MP;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Data
 * @package LR\Callforprice\Helper
 */
class ProductImport extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_productModel;
    protected $authSession;
    protected $pricingHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $_productUrl;

    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    protected $_attributeFactory;

    /**
     * Parameters which contains information regarding import.
     *
     * @var array
     */
    public $params;

    /**
     * Product Object.
     *
     * @var array
     */
    protected $product;

    /**
     * Directory List
     *
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * File interface
     *
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;


    /**
     * Attribute set Collection Object.
     *
     * @var array
     */
    protected $_attributeSetCollection;

    /**
     * Import Type
     *
     * @var string
     */
    protected $importType = 'full';

    /**
     * @var
     */
    protected $mpApi;
    protected $data;


    /**
     *knawat default configuration path value
     */
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $catalogCategoryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
     * @param \Magento\Catalog\Model\Product\Url $productUrl,
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Catalog\Model\Product\Url $productUrl,
        ObjectManagerInterface $objectManager,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Knawat\MPFactory $mpFactory
    ) {
        parent::__construct($context);
        $this->_productModel = $productModel;
        $this->authSession = $authSession;
        $this->pricingHelper = $pricingHelper;
        $this->scopeConfig = $scopeConfig;
        $this->_attributeSetCollection = $attributeSetCollection;
        $this->_eavAttribute = $eavAttribute;
        $this->_objectManager = $objectManager;
        $this->_productUrl = $productUrl;
        $this->_eavSetupFactory = $eavSetupFactory;
        $this->directoryList = $directoryList;
        $this->file = $file;
        // Import parameters.
        $this->mpFactory = $mpFactory;
    }

    public function import($importType = 'full', $params = [])
    {
        $this->importType = $importType;
        $default_args = [
            // 'import_id'         => 0,  // Import_ID
            'limit'             => 25, // Limit for Fetch Products
            'page'              => 1,  // Page Number
            'product_index'     => -1, // product index needed incase of memory issuee or timeout
            // 'force_update'      => false, // Whether to force update existing items.
            'prevent_timeouts'  => true,  // Check memory and time usage and abort if reaching limit.
            'is_complete'       => false, // Is Import Complete?
            'products_total'    => -1,
            'imported'          => 0,
            'failed'            => 0,
            'updated'           => 0,
            'skipped'           => 0
        ];
        $this->params = array_merge($default_args, $params);
        $defaultLanguage = 'en';
        $mp = $this->createMP();
        $websites = $this->getWebsites( false );
        if (empty($websites)) {
            return [ 'status' => 'fail', 'message' => __('Websites are not enabled for import.') ];
        }
        $this->start_time = time();
        $data = [
            'imported' => [],
            'failed'   => [],
            'updated'  => [],
            'skipped'  => [],
        ];

        switch ($this->importType) {
            case 'full':
                $this->data = $mp->getProducts($this->params['limit'], $this->params['page']);
                break;

            case 'single':
                $sku = $this->params['sku'];
                if (empty($sku)) {
                    return [ 'status' => 'fail', 'message' => __('Please provide product sku.') ];
                }
                $this->data = $mp->getProductBySku($sku);
                break;

            default:
                $this->data = null;
                break;
        }

        if (isset($this->data->code) && isset($this->data->message)) {
            return [ 'status' => 'fail', 'message' => $this->data->name.': '.$this->data->message ];
        }

        // Check for Products
        $response = $this->data;
        if (isset($response->products) || ( 'single' === $this->importType && isset($response->product) )) {
            $products = [];
            if ('single' === $this->importType) {
                if (isset($response->product->status) && 'failed' == $response->product->status) {
                    $error_message = isset($response->product->message) ? $response->product->message : __('Something went wrong during get data from Knawat MP API. Please try again later.' );
                    return [ 'status' => 'fail', 'message' => $error_message ];
                }
                $products[] = $response->product;
            } else {
                $products = $response->products;
            }
            // Handle errors
            if (isset($products->code) || !is_array($products)) {
                return [ 'status' => 'fail', 'message' => __('Something went wrong during get data from Knawat MP API. Please try again later.') ];
            }

            // Update Product totals.
            $this->params['products_total'] = count($products);
            if (empty($products)) {
                $this->params['is_complete'] = true;
                return $data;
            }

            // General Variables
            $attributeSetId = $this->getAttrSetId('Knawat');
            $savedAttributes = array();
            foreach ($products as $index => $product) {
                if ($index <= $this->params['product_index']) {
                    continue;
                }

                $formated_data = $this->getFormattedProducts($product);
                if( empty( $formated_data ) ){
                    // Update product index
                    $this->params['product_index'] = $index;
                    continue;
                }
                // Prevent new import for 0 qty products.
                $totalQty = 0;
                $variations = isset($formated_data['variations']) ? $formated_data['variations'] : array();
                if (!empty($variations)) {
                    foreach ($variations as $vars) {
                        $totalQty += isset($vars['stock_quantity']) ? $vars['stock_quantity'] : 0;
                    }
                }

                if( !isset($formated_data['id']) || empty($formated_data['id'] ) ){
                    if( $totalQty == 0 ){
                        $data['skipped'][] = $formated_data['sku'];
                        // Update product index
                        $this->params['product_index'] = $index;
                        continue;
                        // @todo  Log needed here.
                    }
                    if( isset($formated_data['raw_attributes']) ){
                        // Create and Setup Attributes.
                        $savedAttributes = $this->createUpdateAttributes($formated_data['raw_attributes'], $attributeSetId);
                    }
                }

                // Create Update Product Variations.
                $associatedProductIds = [];
                $associatedProductSkus = [];
                $productResource = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product');
                try{
                    foreach ($variations as $variation) {
                        if (isset($variation['id']) && !empty($variation['id']) && $variation['id'] > 0) {

                            // update Exising Product Variation
                            $var_product = $this->_objectManager->create('\Magento\Catalog\Model\Product')->load($variation['id']);
                            $var_product->setPrice($variation['price']); // price of product
                            $var_product->setStockData(
                                [
                                    'is_in_stock'  => ($variation['stock_quantity'] > 0 ) ? 1 : 0,
                                    'qty' => $variation['stock_quantity']
                                ]
                            );
                            $var_product->save();
                        } else {
                            if( empty( $savedAttributes )){
                                if( isset($formated_data['raw_attributes']) ){
                                    // Create and Setup Attributes.
                                    $savedAttributes = $this->createUpdateAttributes($formated_data['raw_attributes'], $attributeSetId);
                                }
                            }
                            // Variation not exists create it.
                            if (!isset($variation['sku']) || empty($variation['sku'])) {
                                continue;
                            }
                            $attributeValues = [];
                            if (isset($variation['raw_attributes']) && !empty($variation['raw_attributes'])) {
                                foreach ($variation['raw_attributes'] as $rawName => $rawValue) {
                                    if (empty($rawName)) {
                                        continue;
                                    }
                                    if (isset($savedAttributes[$rawName]) && !empty($savedAttributes[$rawName])) {
                                        $attrId = $savedAttributes[$rawName]['attr_id'];
                                        $optionId = '';
                                        if (isset($savedAttributes[$rawName]['attr_options'])) {
                                            $attrOptions = $savedAttributes[$rawName]['attr_options'];
                                            $optionId = isset($attrOptions[$rawValue]) ? $attrOptions[$rawValue] : '';
                                        }
                                        if (!empty($attrId) && !empty($optionId)) {
                                            $attributeValues[$savedAttributes[$rawName]['attr_code']] = $optionId;
                                        }
                                    }
                                }
                            }

                            $var_product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
                            $var_product->setSku($variation['sku']); // Set your sku here
                            $var_product->setName($variation['name']); // Name of Product
                            $var_product->setTypeId('simple'); // type of product (simple/virtual/downloadable/configurable)
                            $var_product->setPrice($variation['price']); // price of product
                            $var_product->setStatus(1); // Status on product enabled/ disabled 1/0
                            $var_product->setVisibility(1); // visibilty of product (catalog / search / catalog, search / Not visible individually)
                            $var_product->setWebsiteIds(array_keys($websites));
                            $var_product->setCategoryIds([2]);
                            $var_product->setTaxClassId(0); // Tax class id
                            $var_product->setWeight($variation['weight']); // weight of product
                            $var_product->setData('is_knawat', 1); // $product is product model's object
                            foreach ($attributeValues as $attributeKeyCode => $attributeValue) {
                                $var_product->setData($attributeKeyCode, $attributeValue); //
                            }
                            $var_product->setAttributeSetId($attributeSetId); // Attribute set id
                            $var_product->setStockData(
                                [
                                    'use_config_manage_stock' => 0,
                                    'manage_stock' => $variation['manage_stock'],
                                    'is_in_stock'  => ($variation['stock_quantity'] > 0 ) ? 1 : 0,
                                    'qty' => $variation['stock_quantity']
                                ]
                            );
                            $var_product->save();
                            $var_product_id = $var_product->getId();
                            $associatedProductIds[] = $var_product_id;
                            $associatedProductSkus[] = $var_product->getSku();
                        }
                    }

                    if (isset($formated_data['id']) && !empty($formated_data['id']) && $formated_data['id'] > 0) {
                        $main_product = $this->_objectManager->create('\Magento\Catalog\Model\Product')->load($formated_data['id']);
                        $main_product->setStockData(
                            [
                                'manage_stock' => 1, //manage stock
                                'is_in_stock' => ($totalQty > 0) ? 1 : 0, //Stock Availability
                            ]
                        );
                        $main_product->save();
                        $data['updated'][] = $formated_data['id'];
                        if( !empty( $associatedProductSkus ) ){
                            $linkManagement  = $this->_objectManager->create(\Magento\ConfigurableProduct\Model\LinkManagement::class );
                            foreach( $associatedProductSkus as $associatedProductSku ){
                                try {
                                    $linkManagement->addChild($main_product->getSku(), $associatedProductSku);
                                } catch (Exception $e) {
                                    // ignore.
                                }
                            }
                        }
                    }else{
                        // Main Product.
                        $main_product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
                        $main_product->setSku($formated_data['sku']); // Set your sku here
                        $main_product->setName($formated_data['name']); // Name of Product
                        $main_product->setDescription($formated_data['description']); // Descripion of Product
                        $main_product->setAttributeSetId($attributeSetId); // Attribute set id
                        $main_product->setStatus(1);
                        $main_product->setTypeId('configurable');
                        $main_product->setWebsiteIds(array_keys($websites));
                        $main_product->setCategoryIds([2]);
                        $main_product->setStockData(
                            [
                                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                                'manage_stock' => 1, //manage stock
                                'is_in_stock' => ($totalQty > 0) ? 1 : 0, //Stock Availability
                            ]
                        );

                        $configurableAttributesIds = [];
                        foreach ($savedAttributes as $savedAttribute) {
                            if (isset($savedAttribute['attr_id'])) {
                                $configurableAttributesIds[] = $savedAttribute['attr_id'];
                            }
                        }
                        $configurableAttributesIds = array_unique($configurableAttributesIds); // Super Attribute Ids Used To Create Configurable Product

                        $main_product->setAffectConfigurableProductAttributes($attributeSetId);
                        $main_product->getTypeInstance()->setUsedProductAttributeIds($configurableAttributesIds, $main_product);
                        $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($configurableAttributesIds, $main_product);
                        $main_product->setNewVariationsAttributeSetId($attributeSetId); // Setting Attribute Set Id
                        $main_product->setAssociatedProductIds($associatedProductIds);// Setting Associated Products
                        $main_product->setCanSaveConfigurableAttributes(true);
                        if( isset($savedAttributes['info_attribute']) && !empty( $savedAttributes['info_attribute'] ) ){
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                $infoAttrib = current( $infoAttribute );
                                $infoAttrib = isset($infoAttrib->$defaultLanguage) ? $infoAttrib->$defaultLanguage : '';
                                $main_product->setData($infoKey, $infoAttrib);
                            }
                        }

                        if( isset( $formated_data['images'] ) && !empty( $formated_data['images'] ) ){
                            $this->importImages( $main_product, $formated_data['images'] );
                        }
                        $main_product->save();
                        $productId = $main_product->getId(); // Configurable Product Id

                        foreach ($websites as $webKey => $website) {
                            foreach ($website as $storeKey => $store) {
                                $storeLang = $store['lang'];
                                $storeProductName = isset($formated_data['name_i18n']->$storeLang) ? $formated_data['name_i18n']->$storeLang : '';
                                $storeProductDesc = isset($formated_data['description_i18n']->$storeLang) ? $formated_data['description_i18n']->$storeLang : '';

                                $main_product->setStoreId($storeKey);
                                $main_product->setName($storeProductName);
                                $main_product->setDescription($storeProductDesc);
                                $productResource->saveAttribute($main_product, 'name');
                                $productResource->saveAttribute($main_product, 'description');
                            }
                        }

                        if( isset($savedAttributes['info_attribute']) && !empty( $savedAttributes['info_attribute'] ) ){
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                foreach ($websites as $webKey => $website) {
                                    foreach ($website as $storeKey => $store) {
                                        $storeLang = isset( $store['lang'] ) ? $store['lang'] : $defaultLanguage;
                                        $infoAttrib = current( $infoAttribute );
                                        $infoAttrib = isset($infoAttrib->$storeLang) ? $infoAttrib->$storeLang : '';
                                        $main_product->addAttributeUpdate($infoKey, $infoAttrib, $storeKey);
                                    }
                                }
                            }
                        }

                        $position = 0;
                        $attributeModel = $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
                        foreach ($configurableAttributesIds as $attributeId) {
                            $attribData = ['attribute_id' => $attributeId, 'product_id' => $productId, 'position' => $position];
                            $position++;
                            $attributeModel->setData($attribData)->save();
                        }
                        $data['imported'][] = $productId;
                    }
                } catch (Exception $e) {
                    // echo $e->getMessage();
                    if( isset( $formated_data['id'] ) ){
                        $data['failed'][] = $formated_data['id'];
                    }elseif( isset( $formated_data['sku'] ) ){
                        $data['failed'][] = $formated_data['sku'];
                    }
                    // @todo logger here.
                }
                // Update product index
                $this->params['product_index'] = $index;

                // Prevent Timeout and Memory exceed.
                /* if ( $this->params['prevent_timeouts'] && ( $this->time_exceeded() || $this->memory_exceeded() ) ) {
                    break;
                } */
            }

            if( $this->params['products_total'] === 0 ){
                $this->params['is_complete'] = true;
            }else{
                $this->params['is_complete'] = false;
            }
            return $data;

        } else {
            return array( 'status' => 'fail', 'message' => __( 'Something went wrong during get data from Knawat MP API. Please try again later.' ) );
        }
    }

    public function getFormattedProducts($product)
    {
        $default_lang = '';
        if (empty($product)) {
            return $product;
        }
        $defaultLanguage = 'en';
        $websites = $this->getWebsites();
        $new_product = [];
        $attributes = [];

        $sku = $product->sku;
        $product_id = $this->getProductBySku($sku);
        if ($product_id) {
            $new_product['id'] = $product_id;
            $new_product['name'] = '';
            if (isset($product->name->$defaultLanguage)) {
                $new_product['name'] = $product->name->$defaultLanguage;
            }
        } else {
            $new_product['sku'] = $product->sku;
        }
        if (!$product_id) {
            if (isset($product->variations) && !empty($product->variations)) {
                $new_product['type'] = 'configurable';
            }
            $default_lang = $this->getAdminLanguage();
            $new_product['name'] = $new_product['description'] = '';
            if (isset($product->name->$defaultLanguage)) {
                $new_product['name'] = $product->name->$defaultLanguage;
            }
            if (isset($product->description->$defaultLanguage)) {
                $new_product['description'] = $product->description->$defaultLanguage;
            }
            $new_product['name_i18n'] = isset( $product->name ) ? $product->name : '';
            $new_product['description_i18n'] = isset($product->description) ? $product->description : '';

            if (isset($product->images) && !empty($product->images)) {
                $new_product['images'] = $product->images;
            }

            if (isset($product->attributes) && !empty($product->attributes)) {
                foreach ($product->attributes as $attribute) {
                    if (!isset($attribute->name) || empty($attribute->name)) {
                        continue;
                    }

                    $defaultCode =  isset($attribute->name->en) ?  $attribute->name->en : '';
                    if (empty($defaultCode)) {
                        $defaultCode =  isset($attribute->name->tr) ?  $attribute->name->tr : '';
                    }
                    $attributeName = isset($attribute->name->$defaultLanguage) ?  $attribute->name->$defaultLanguage : '';

                    // Continue if no attribute name found.
                    if (empty($attributeName) || empty($defaultCode)) {
                        continue;
                    }

                    $attributeOptions = [];
                    if (isset($attribute->options) && !empty($attribute->options)) {
                        foreach ($attribute->options as $attributeValue) {
                            if (isset($attributeValue->$defaultLanguage)) {
                                $attributeFormated = $attributeValue->$defaultLanguage;
                            }
                            if (!array_key_exists($attributeFormated, $attributeOptions) && !empty($attributeFormated)) {
                                $attributeOptions[$attributeFormated] = $attributeValue;
                            }
                        }
                    }
                    $attributeOptions = [
                        'name_i18n' => $attribute->name,
                        'value'     => $attributeOptions,
                        'attr_code' => $defaultCode
                    ];

                    if (isset($attributes[ $attributeName ])) {
                        if (!empty($attributeOptions)) {
                            $attributes[ $attributeName ] = array_unique(array_merge($attributes[ $attributeName ], $attributeOptions));
                        }
                    } else {
                        $attributes[ $attributeName ] = $attributeOptions;
                    }
                }
            }
        }

        $variations = [];
        $var_attributes = [];
        if (isset($product->variations) && !empty($product->variations)) {
            foreach ($product->variations as $variation) {
                $temp_variant = [];
                $varient_id = $this->getProductBySku($variation->sku);
                if ($varient_id && $varient_id > 0) {
                    $temp_variant['id'] = $varient_id;
                } else {
                    $temp_variant['sku']  = $variation->sku;
                    $temp_variant['name'] = isset($new_product['name']) ? $new_product['name'] : '';
                    $temp_variant['type'] = 'simple';
                }
                if (isset($variation->sale_price) && is_numeric($variation->sale_price)) {
                    $temp_variant['price'] = $temp_variant['final_price'] = round(floatval($variation->sale_price), 2);
                }
                if (isset($variation->market_price) && is_numeric($variation->market_price)) {
                    $temp_variant['regular_price'] = round(floatval($variation->market_price), 2);
                }
                $temp_variant['manage_stock'] = true;
                $temp_variant['stock_quantity'] = isset( $variation->quantity ) ? $variation->quantity : 0;
                if ($varient_id && $varient_id > 0) {
                    // Update Data for existing Variend Here.
                } else {
                    $temp_variant['weight'] = isset($variation->weight) ? round(floatval($variation->weight), 2) : 0;
                
                    if (isset($variation->attributes) && !empty($variation->attributes)) {
                        foreach ($variation->attributes as $attribute) {
                            // continue if no attribute name found.
                            if (!isset($attribute->name) || empty($attribute->name) || !isset($attribute->option)) {
                                continue;
                            }
                            $defaultCode =  isset($attribute->name->en) ?  $attribute->name->en : '';
                            if (empty($defaultCode)) {
                                $defaultCode =  isset($attribute->name->tr) ?  $attribute->name->tr : '';
                            }
                            $defaultAttributeName = isset($attribute->name->$defaultLanguage) ? $attribute->name->$defaultLanguage : '';
                            $defaultAttributeValue = isset($attribute->option->$defaultLanguage) ? $attribute->option->$defaultLanguage : '';
                            if (empty($defaultAttributeName) || empty($defaultAttributeValue)) {
                                continue;
                            }

                            $temp_variant['raw_attributes'][$defaultAttributeName] = $defaultAttributeValue;

                            // Add attribute name to $var_attributes for make it taxonomy.
                            $var_attributes[] = $defaultAttributeName;

                            if (isset($attributes[ $defaultAttributeName ])) {
                                if (!array_key_exists($defaultAttributeValue, $attributes[ $defaultAttributeName ]['value'])) {
                                    $attributes[ $defaultAttributeName ]['value'][$defaultAttributeValue] = $attribute->option;
                                }
                            } else {
                                $attributes[ $defaultAttributeName ]['name_i18n'] = $attribute->name;
                                $attributes[ $defaultAttributeName ]['value'][$defaultAttributeValue] = $attribute->option;
                                $attributes[ $defaultAttributeName ]['attr_code'] = $defaultCode;
                            }
                        }
                        if( isset ($temp_variant['raw_attributes'] ) && !empty($temp_variant['raw_attributes'])){
                            $temp_variant['name'] .= '-'.implode('-', $temp_variant['raw_attributes']);
                        }
                    }
                }
                $variations[] = $temp_variant;
            }
        }
        if (!empty($attributes)) {
            foreach ($attributes as $name => $value) {
                $temp_raw = [];
                $temp_raw['name'] = $name;
                $temp_raw['value'] = $value;
                $temp_raw['attr_code'] = isset($value['attr_code']) ? $value['attr_code'] : '';
                $temp_raw['visible'] = true;
                if (in_array($name, $var_attributes)) {
                    $temp_raw['taxonomy'] = true;
                    $temp_raw['default'] = isset($value[0]) ? $value[0] : '';
                }
                $new_product['raw_attributes'][] = $temp_raw;
            }
        }
        $new_product['variations'] = $variations;
        return $new_product;
    }

    /**
     * Get ProductModel by SKU
     *
     * @param string $sku
     * @return object $this->_productModel
     */
    public function getProductBySku($sku)
    {
        return $product = $this->_productModel->getIdBySku($sku);
    }

    /**
     * Get Admin interface Language
     *
     * @return string Language Code
     */
    public function getAdminLanguage()
    {
        $language = $this->authSession->getUser()->getInterfaceLocale();
        $language = explode(',', $language);
        if (array_key_exists(0, $language)) {
            $language_code = explode('_', $language[0]);
            if ($language_code[0] == 'ar' || $language_code[0] == 'en' || $language_code[0] == 'tr') {
                $language_identifier = $language_code[0];
            } else {
                $language_identifier = "en";
            }
        }
        return $language_identifier;
    }

    /**
     * Get config data from DB
     *
     * @param string $path
     * @return string
     */
    public function getConfigData($path, $store = 'default', $scopeId = 0)
    {
        return $this->scopeConfig->getValue(self::PATH_KNAWAT_DEFAULT.$path, $store, $scopeId);
    }

    public function getAttrSetId($attrSetName)
    {
        $attributeSet = $this->_attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter(
            'attribute_set_name',
            $attrSetName
        );
        $attributeSetId = 0;
        foreach ($attributeSet as $attr) :
            $attributeSetId = $attr->getAttributeSetId();
        endforeach;
        return $attributeSetId;
    }

    public function getAttributeId($attributeCode)
    {
        return $this->_eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
    }

    /**
     * Create Attributes for given attribute Set.
     *
     * @param Array $attributes
     * @param String $attrSetId
     * @return void
     */
    public function createUpdateAttributes($attributes, $attrSetId)
    {
        if (empty($attributes)) {
            return;
        }
        $formattedAttributes = [];
        foreach ($attributes as $attribKey => $attribute) {
            if (!isset($attribute['name']) || empty($attribute['name']) || !isset($attribute['attr_code']) || !isset($attribute['value'])) {
                continue;
            }
            $attributeCode = $this->generateAttributeCode($attribute['attr_code']);
            if (!isset($attribute['taxonomy'])) {
                $attributeCode = $this->generateAttributeCode($attribute['attr_code'], true);
            }
            $attributeId = $this->getAttributeId($attributeCode);
            $formattedAttributes[$attribute['name']] = [];
            $websites = $this->getWebsites();
            if (empty($attributeId)) {
                $new_attribute = $this->_objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
                $categorySetup = $this->_objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
                $entityTypeId = $categorySetup->getEntityTypeId('catalog_product');
                $storeLabels = [];
                foreach ($websites as $webKey => $website) {
                    foreach ($website as $storeKey => $store) {
                        $storeLang = $store['lang'];
                        $storeLabels[$storeKey] = isset($attribute['value']['name_i18n']->$storeLang) ? $attribute['value']['name_i18n']->$storeLang : $attribute['name'];
                    }
                }
                if (empty($storeLabels)) {
                    $storeLabels = [ $attribute['name'] ];
                }
                if (isset($attribute['taxonomy']) && $attribute['taxonomy'] == '1') {
                    // Create Dropdown Attribute
                    $attributeValues = [];
                    if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                        foreach ($attribute['value']['value'] as $valueKey => $value) {
                            $storeValue = [];
                            foreach ($websites as $webKey => $website) {
                                foreach ($website as $storeKey => $store) {
                                    $storeLang = $store['lang'];
                                    $storeValue[$storeKey] = isset($value->$storeLang) ? $value->$storeLang : $valueKey;
                                }
                            }
                            if (empty($storeValue)) {
                                $storeValue = [ $valueKey ];
                            }
                            $attributeValues[$this->generateAttributeValueKey($valueKey)] = $storeValue;
                        }
                    }
                    $new_attribute->setData(
                        [
                            'attribute_code'                => $attributeCode,
                            'entity_type_id'                => $entityTypeId,
                            'is_global'                     => 1,
                            'is_user_defined'               => 1,
                            'frontend_input'                => 'select',
                            'is_unique'                     => 0,
                            'is_required'                   => 0,
                            'is_searchable'                 => 1,
                            'is_visible_in_advanced_search' => 1,
                            'is_comparable'                 => 1,
                            'is_filterable'                 => 1,
                            'is_filterable_in_search'       => 0,
                            'is_used_for_promo_rules'       => 0,
                            'is_html_allowed_on_front'      => 0,
                            'is_visible_on_front'           => 0,
                            'used_in_product_listing'       => 0,
                            'used_for_sort_by'              => 0,
                            'frontend_label'                => $attribute['name'],
                            'store_labels'                  => $storeLabels,
                            'backend_type'                  => 'varchar',
                            'backend_model'                 => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                            'option'                        => [ 'value' => $attributeValues ],
                        ]
                    );
                    $new_attribute->save();
                    /* Assign attribute to attribute set */
                    $categorySetup->addAttributeToGroup('catalog_product', 'Knawat', 'General', $new_attribute->getId());

                    $attributeId = $new_attribute->getId();
                    $attributeCode = $new_attribute->getAttributeCode();
                    $productAttributeRepository = $this->_objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
                    $options = $productAttributeRepository->get($attributeCode)->getOptions();
                    foreach ($options as $option) {
                        if (!empty(trim($option->getLabel()))) {
                            $existingOptions[$option->getLabel()] = $option->getValue();
                        }
                    }

                    $formattedAttributes[$attribute['name']]['attr_id'] = $attributeId;
                    $formattedAttributes[$attribute['name']]['attr_code'] = $attributeCode;
                    $formattedAttributes[$attribute['name']]['attr_options'] = $existingOptions;

                } else {
                    // Create Normal Attribute
                    $new_attribute->setData(
                        [
                            'attribute_code'                => $attributeCode,
                            'entity_type_id'                => $entityTypeId,
                            'frontend_input'                => 'text',
                            'is_global'                     => 0,
                            'is_user_defined'               => 1,
                            'is_unique'                     => 0,
                            'is_required'                   => 0,
                            'is_searchable'                 => 0,
                            'is_visible_in_advanced_search' => 0,
                            'is_comparable'                 => 0,
                            'is_filterable'                 => 0,
                            'is_filterable_in_search'       => 0,
                            'is_used_for_promo_rules'       => 0,
                            'is_html_allowed_on_front'      => 0,
                            'is_visible_on_front'           => 1,
                            'used_in_product_listing'       => 0,
                            'used_for_sort_by'              => 0,
                            'frontend_label'                => $attribute['name'],
                            'store_labels'                  => $storeLabels,
                            'backend_type'                  => 'text',
                        ]
                    );

                    $new_attribute->save();
                    /* Assign attribute to attribute set */
                    $categorySetup->addAttributeToGroup('catalog_product', 'Knawat', 'Attributes', $new_attribute->getId());
                    $attributeCode = $new_attribute->getAttributeCode();
                    $attValues = array();
                    if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                        $attValues = $attribute['value']['value'];
                    }
                    $formattedAttributes['info_attribute'][$attributeCode] = $attValues;
                }
            } else {
                if (isset($attribute['taxonomy']) && $attribute['taxonomy'] == '1') {
                    $existingOptions = [];
                    $existingAttribute = $this->_objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)->load($attributeId);
                    $attributeCode = $existingAttribute->getAttributeCode();
                    $productAttributeRepository = $this->_objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
                    $options = $productAttributeRepository->get($attributeCode)->getOptions();
                    foreach ($options as $option) {
                        if (!empty(trim($option->getLabel()))) {
                            $existingOptions[$option->getLabel()] = $option->getValue();
                        }
                    }
                    // Add Aditional Option.
                    $reloadOptions = false;
                    if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                        foreach ($attribute['value']['value'] as $valueKey => $value) {
                            if (!array_key_exists($valueKey, $existingOptions)) {
                                $storeOption = [];
                                foreach ($websites as $webKey => $website) {
                                    foreach ($website as $storeKey => $store) {
                                        $storeLang = $store['lang'];
                                        $storeOption[$storeKey] = isset($value->$storeLang) ? $value->$storeLang : $valueKey;
                                    }
                                }
                                if (empty($storeOption)) {
                                    $storeOption = [ $valueKey ];
                                }

                                $_option = $this->_objectManager->create(\Magento\Eav\Model\Entity\Attribute\Option::class);
                                $_attributeOptionManagement = $this->_objectManager->create(\Magento\Eav\Api\AttributeOptionManagementInterface::class);
                                $_attributeOptionLabel = $this->_objectManager->create(\Magento\Eav\Api\Data\AttributeOptionLabelInterface::class);

                                $storeLabels = '';
                                foreach ($storeOption as $sKey => $sOption) {
                                    $_attributeOptionLabel2 = $this->_objectManager->create(\Magento\Eav\Api\Data\AttributeOptionLabelInterface::class);
                                    $_attributeOptionLabel2->setStoreId($sKey);
                                    $_attributeOptionLabel2->setLabel($sOption);
                                    $storeLabels[$sKey] = $_attributeOptionLabel2;
                                }
                                $_attributeOptionLabel->setStoreId(0);
                                $_attributeOptionLabel->setLabel($valueKey);
                                $_option->setLabel($_attributeOptionLabel);
                                $_option->setStoreLabels($storeLabels);
                                $_option->setSortOrder(0);
                                $_option->setIsDefault(false);
                                $_attributeOptionManagement->add('catalog_product', $attributeId, $_option);
                                $reloadOptions = true;
                            }
                        }
                    }
                    if ($reloadOptions) {
                        $existingOptions = [];
                        $options = $productAttributeRepository->get($attributeCode)->getOptions();
                        foreach ($options as $option) {
                            if (!empty(trim($option->getLabel()))) {
                                $existingOptions[$option->getLabel()] = $option->getValue();
                            }
                        }
                    }
                    $formattedAttributes[$attribute['name']]['attr_id'] = $attributeId;
                    $formattedAttributes[$attribute['name']]['attr_code'] = $attributeCode;
                    $formattedAttributes[$attribute['name']]['attr_options'] = $existingOptions;
                }else{
                    $attValues = array();
                    if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                        $attValues = $attribute['value']['value'];
                    }
                    $formattedAttributes['info_attribute'][$attributeCode] = $attValues;
                }
            }
        }
        return $formattedAttributes;
    }

    /**
     * Generate attribute code from label
     *
     * @param string $label
     * @return string
     */
    protected function generateAttributeCode($label, $isNormal = false)
    {
        if( $isNormal){
            $label = 'k_'.$label;
        }
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->_productUrl->formatUrlKey($label)
            ),
            0,
            25
        );
        $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,24}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = ($code ?: substr(md5(time()), 0, 8));
        }
        return $code."_knawat";
    }

    /**
     * Generate attribute code from label
     *
     * @param string $label
     * @return string
     */
    protected function generateAttributeValueKey($value)
    {
        $valueKey = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->_productUrl->formatUrlKey($value)
            ),
            0,
            30
        );
        $validatorAttrValue = new \Zend_Validate_Regex(['pattern' => '/^[a-z_0-9]+$/']);
        if (!$validatorAttrValue->isValid($valueKey)) {
            $valueKey = 'att_val_'.($valueKey ?: substr(md5(time()), 0, 8));
        }
        return $valueKey;
    }

    /**
     * Get Websites with store views and languages
     *
     * @param  boolean $withAdmin
     * @return array websites
     */
    protected function getWebsites( $withAdmin = true )
    {
        $_storeRepository = $this->_objectManager->create('Magento\Store\Model\StoreRepository');
        $stores = $_storeRepository->getList();
        $websites = [];
        $enabaledWebsites = array();
        foreach ($stores as $store) {
            if( $store["code"] == 'admin' && !$withAdmin ){
                continue;
            }
            $websiteId = $store["website_id"];
            if( in_array( $websiteId, $enabaledWebsites ) ){
                continue;
            }

            $defaultConsumerKey = $this->getConfigData('consumer_key');
            $defaultConsumerSecret = $this->getConfigData('consumer_secret');
            if(!empty($defaultConsumerKey) && !empty($defaultConsumerSecret)){
                $enabaledWebsites[] = $websiteId;
            }else{
                $websiteConsumerKey = $this->getConfigData('consumer_key',"websites",$websiteId);
                $websiteConsumerSecret = $this->getConfigData('consumer_secret',"websites",$websiteId);
                if(!empty($websiteConsumerKey) && !empty($websiteConsumerSecret)){
                        $enabaledWebsites[] = $websiteId;
                }
            }
        }
        foreach ($stores as $store) {
            if( $store["code"] == 'admin' && !$withAdmin ){
                continue;
            }
            $websiteId = $store["website_id"];
            if( !in_array( $websiteId, $enabaledWebsites ) ){
                continue;
            }
            $storeId = $store["store_id"];
            $storeData = [
                'code' => $store["code"]
            ];
            $defaultConfigLanguage = $this->getConfigData('store_language');
            $storeData['lang'] = $defaultConfigLanguage;
            $storeConfigLanguage = $this->getConfigData('store_language',"stores",$storeId);
            if (!empty($storeConfigLanguage)) {
                $storeData['lang'] = $storeConfigLanguage;
                $websiteConfigLanguage = $this->getConfigData('store_language',"stores",$websiteId);
                if (!empty($websiteConfigLanguage)) {
                    $storeData['lang'] = $websiteConfigLanguage;
                }
            }
            $websites[$websiteId][$storeId] = $storeData;
        }
        return $websites;
    }

    /**
     * @return MP
     */
    protected function createMP()
    {
        $consumer_key = $this->getConfigData('consumer_key');
        $consumer_secret = $this->getConfigData('consumer_secret');
        if ($this->mpApi == null) {
            $mp = $this->mpFactory->create([
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
            ]);

            return $this->mpApi = $mp;
        } else {
            return $this->mpApi;
        }
    }

    /**
	 * Get Import Parameters
	 *
	 * @param string $value Field value.
	 * @return float|string
	 */
	public function getImportParams(){
		return $this->params;
    }

    /**
     * Product Import Images from URL
     *
     * @param Product $product
     * @param string $imageUrl
     * @param array $imageType
     * @param bool $visible
     *
     * @return bool
     */
    public function importImages($product, $imageUrls )
    {
        if( empty( $imageUrls ) ){
            return false;
        }

        // Temporory Directory for Image.
        $tmpDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
        // Create folder if it is not exists.
        $this->file->checkAndCreateFolder($tmpDir);

        foreach( $imageUrls as $index => $imageUrl){
            // File Path for download image.
            $newFileName = $tmpDir . DIRECTORY_SEPARATOR . mt_rand() . baseName($imageUrl);
            $imageType = null;
            if( $index == 0){
                $imageType = ['image', 'small_image', 'thumbnail'];
            }
            // Download file from remote URL and Add to Temp Directory/
            $ch = curl_init ( $imageUrl );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_BINARYTRANSFER,1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            $raw_image_data = curl_exec( $ch );
            curl_close ( $ch );
            try{
                $image = fopen( $newFileName,'w' );
                fwrite( $image, $raw_image_data );
                fclose( $image );
            }catch( Exception $e ){
                continue;
            }
            $product->addImageToMediaGallery($newFileName, $imageType, true, false);
        }
    }
}
