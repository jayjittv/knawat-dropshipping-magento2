<?php
namespace Knawat\Dropshipping\Helper;

use Knawat\MP;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Data
 * @package LR\Callforprice\Helper
 */
class productImport extends \Magento\Framework\App\Helper\AbstractHelper
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
    protected $import_type = 'full';

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
        \Magento\Catalog\Model\Product\Url $productUrl,
        ObjectManagerInterface $objectManager,
        \Knawat\MPFactory $mpFactory,
        $import_type = 'full',
        array $params = []
    ) {
        $default_args = array(
            'import_id'         => 0,  // Import_ID
            'limit'             => 25, // Limit for Fetch Products
            'page'              => 1,  // Page Number
            'product_index'     => -1, // product index needed incase of memory issuee or timeout
            'force_update'      => false, // Whether to force update existing items.
            'prevent_timeouts'  => true,  // Check memory and time usage and abort if reaching limit.
            'is_complete'       => false, // Is Import Complete?
            'products_total'    => -1,
            'imported'          => 0,
            'failed'            => 0,
            'updated'           => 0,
        );
        parent::__construct($context,$params);
        $this->_productModel = $productModel;
        $this->authSession = $authSession;
        $this->pricingHelper = $pricingHelper;
        $this->scopeConfig = $scopeConfig;
        $this->_attributeSetCollection = $attributeSetCollection;
        $this->_eavAttribute = $eavAttribute;
        $this->_objectManager = $objectManager;
        $this->_productUrl = $productUrl;
        // Import parameters.
        $this->mpFactory = $mpFactory;
        $this->params = array_merge( $default_args, $params );
    }

    public function Import(){
        $mp = $this->createMP();

        $this->start_time = time();
        $data = array(
            'imported' => array(),
            'failed'   => array(),
            'updated'  => array(),
        );

        switch ( $this->import_type ) {
            case 'full':
                $this->data = $mp->getProducts( $this->params['limit'], $this->params['page'] );
                break;

            case 'single':
                $sku = sanitize_text_field( $this->params['sku'] );
                if( empty( $sku ) ){
                    return array( 'status' => 'fail', 'message' => __( 'Please provide product sku.', 'dropshipping-woocommerce' ) );
                }
                $this->data = $mp->getProductBySku( $sku );
                break;

            default:
                $this->data = null;
                break;
        }

        if( isset( $this->data->code ) && isset( $this->data->message ) ){
            return array( 'status' => 'fail', 'message' => $this->data->name.': '.$this->data->message );
        }

        // Check for Products
        $response = $this->data;
        if( isset( $response->products ) || ( 'single' === $this->import_type && isset( $response->product ) ) ){
            $products = array();
            if ( 'single' === $this->import_type ) {
                if( isset( $response->product->status ) && 'failed' == $response->product->status ){
                    $error_message = isset( $response->product->message ) ? $response->product->message : __( 'Something went wrong during get data from Knawat MP API. Please try again later.', 'dropshipping-woocommerce' );
                    return array( 'status' => 'fail', 'message' => $error_message );
                }
                $products[] = $response->product;
            }else{
                $products = $response->products;
            }
            // Handle errors
            if( isset( $products->code ) || !is_array( $products ) ){
                return array( 'status' => 'fail', 'message' => __( 'Something went wrong during get data from Knawat MP API. Please try again later.', 'dropshipping-woocommerce' ) );
            }

            // Update Product totals.
            $this->params['products_total'] = count( $products );
            if( empty( $products ) ){
                $this->params['is_complete'] = true;
                return $data;
            }

            // General Variables
            $attributeSetId = $this->getAttrSetId('Knawat');
            foreach( $products as $index => $product ){
                if( $index <= $this->params['product_index'] ){
                    continue;
                }
                $formated_data = $this->getFormattedProducts($product);
                // Create and Setup Attributes.
                $savedAttributes = $this->createUpdateAttributes( $formated_data['raw_attributes'], $attributeSetId );

                $full_updateNeeded = 0;
                // Prevent new import for 0 qty products.
                $total_qty = 0;
                $variations = $formated_data['variations'];
                if( !empty( $variations )){
                    foreach ($variations as $vars) {
                        $total_qty += isset($vars['stock_quantity']) ? $vars['stock_quantity'] : 0;
                    }
                }

                // Create Update Product Variations.
                $configurableProductsData = array();
                $associatedProductIds = array();
                foreach( $variations as $variation ){
                    if(isset($variation['id']) && !empty($variation['id']) && $variation['id'] > 0){

                    }else{
                        // Variation not exists create it.
                        if(!isset($variation['sku']) || empty($variation['sku'])){
                            continue;
                        }
                        $attributeValues = array();
                        $varAttibuteData = array();
                        if( isset($variation['raw_attributes']) && !empty($variation['raw_attributes']) ){
                            foreach( $variation['raw_attributes'] as $raw_attribute ){
                                $rawName = isset($raw_attribute['name']) ? $raw_attribute['name'] : '';
                                if( empty($rawName)){
                                    continue;
                                }
                                if( isset( $savedAttributes[$rawName] ) && !empty( $savedAttributes[$rawName] ) ){
                                    $rawValue = $raw_attribute['value'][0];
                                    echo $attrId = $savedAttributes[$rawName]['attr_id'];
                                    $optionId = '';
                                    if( isset($savedAttributes[$rawName]['attr_options']) ){
                                        $attrOptions = $savedAttributes[$rawName]['attr_options'];
                                        echo $optionId = isset($attrOptions[$rawValue]) ? $attrOptions[$rawValue] : '';
                                    }
                                    if( !empty($attrId) && !empty($optionId) ){
                                        $varAttibuteData = array(
                                            'label'         => $rawValue, //attribute label
                                            'attribute_id'  => $attrId,   //attribute ID of attribute
                                            'value_index'   => $optionId, // Value for Attribute option
                                            'is_percent'    => 0,
                                            'pricing_value' => 0
                                        );
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
                        $var_product->setWebsiteIds(array(1));
                        $var_product->setCategoryIds(array(2));
                        $var_product->setTaxClassId(0); // Tax class id
                        $var_product->setWeight($variation['weight']); // weight of product
                        $var_product->setData('is_knawat', 1); // $product is product model's object
                        foreach( $attributeValues as $attributeKeyCode => $attributeValue ){
                            $var_product->setData( $attributeKeyCode, $attributeValue ); // 
                        }
                        $variation['stock_quantity'] = 20;
                        $var_product->setAttributeSetId($attributeSetId); // Attribute set id
                        $var_product->setStockData(
                            array(
                                'use_config_manage_stock' => 0,
                                'manage_stock' => $variation['manage_stock'],
                                'is_in_stock'  => ($variation['stock_quantity'] > 0 ) ? 1 : 0,
                                'qty' => $variation['stock_quantity']
                            )
                        );
                        $var_product->save();
                        $var_product_id = $var_product->getId();
                        $associatedProductIds[] = $var_product_id;
                        if( !empty( $varAttibuteData)){
                            $configurableProductsData[$var_product_id][] = $varAttibuteData;
                        }
                    }
                }

                // Main Product.
                $main_product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
                $main_product->setSku($formated_data['sku']); // Set your sku here
                $main_product->setName($formated_data['name']); // Name of Product
                $main_product->setAttributeSetId($attributeSetId); // Attribute set id
                $main_product->setStatus(1);
                $main_product->setTypeId('configurable');
                $main_product->setWebsiteIds(array(1));
                $main_product->setCategoryIds(array(2));
                $main_product->setStockData(array(
                    'use_config_manage_stock' => 0, //'Use config settings' checkbox
                    'manage_stock' => 1, //manage stock
                    'is_in_stock' => 1, //Stock Availability
                    )
                );

                $configurableAttributesIds = array();
                foreach( $savedAttributes as $savedAttribute ){
                    if( isset( $savedAttribute['attr_id'] ) ){
                        $configurableAttributesIds[] = $savedAttribute['attr_id'];
                    }
                }
                $configurableAttributesIds = array_unique( $configurableAttributesIds ); // Super Attribute Ids Used To Create Configurable Product

                $main_product->setAffectConfigurableProductAttributes($attributeSetId);
                $main_product->getTypeInstance()->setUsedProductAttributeIds($configurableAttributesIds, $main_product);
                $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($configurableAttributesIds, $main_product);
                $main_product->setNewVariationsAttributeSetId($attributeSetId); // Setting Attribute Set Id
                $main_product->setAssociatedProductIds($associatedProductIds);// Setting Associated Products
                $main_product->setCanSaveConfigurableAttributes(true);
                $main_product->save();
                $productId = $main_product->getId(); // Configurable Product Id

                $position = 0;
                $attributeModel = $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
                foreach ($configurableAttributesIds as $attributeId) {
                    $data = array('attribute_id' => $attributeId, 'product_id' => $productId, 'position' => $position);
                    $position++;
                    $attributeModel->setData($data)->save();
                }

                echo "configurable product id: ".$main_product->getId()."\n";
                return $formated_data;
            }

        }else{

        }
    }

    public function getFormattedProducts($product){
        $default_lang = '';
        if( empty( $product) ){
            return $product;
        }
        $new_product = array();
        $attributes = array();

        $sku = $product->sku;
        $product_id = $this->getProductBySku($sku);
        if ($product_id) {
            $new_product['id'] = $product_id;
        }else{
            $new_product['sku'] = $product->sku;
        }
        if( !$product_id){
            if( isset( $product->variations ) && !empty( $product->variations ) ){
                $new_product['type'] = 'configurable';
            }
            $default_lang = $this->getAdminLanguage();
            $new_product['name'] = $new_product['description'] = '';
            if(isset($product->description->$default_lang)){
                $new_product['name'] = $product->name->$default_lang;
            }
            if(isset($product->description->$default_lang)){
                $new_product['description'] = $product->description->$default_lang;
            }

            if(isset($product->images) && !empty($product->images)){
                $images = $product->images;
                $new_product['main_image_id'] = array_shift( $images );
                if ( ! empty( $images ) ) {
                    $new_product['gallery_image_ids'] = $images;
                }
            }

            if( isset( $product->attributes ) && !empty( $product->attributes ) ){
                foreach ( $product->attributes as $attribute ) {
                    $attribute_name = isset( $attribute->name ) ?  $attribute->name->$default_lang : '';
                    $attribute_options = array();
                    if( isset( $attribute->options ) && !empty( $attribute->options ) ){
                        foreach ($attribute->options as $attributevalue) {
                            if(isset($attributevalue->$default_lang)){
                                $attribute_formated = $attributevalue->$default_lang;
                            }
                            if( !in_array( $attribute_formated, $attribute_options ) && !empty( $attribute_formated ) ){
                                $attribute_options[] = $attribute_formated;
                            }
                        }
                    }
                    // continue if no attribute name found.
                    if( $attribute_name == '' ){
                        continue;
                    }

                    if( isset( $attributes[ $attribute_name ] ) ){
                        if( !empty( $attribute_options ) ){
                            $attributes[ $attribute_name ] = array_unique( array_merge( $attributes[ $attribute_name ], $attribute_options ) );
                        }
                    }else{
                        $attributes[ $attribute_name ] = $attribute_options;
                    }
                }
            }
        }

        $variations = array();
        $var_attributes = array();
        if( isset( $product->variations ) && !empty( $product->variations ) ){
            foreach ( $product->variations as $variation ) {
                $temp_variant = array();
                $varient_id = $this->getProductBySku($variation->sku) ;
                if ( $varient_id && $varient_id > 0 ) {
                    $temp_variant['id'] = $varient_id;
                }else{
                    $temp_variant['sku']  = $variation->sku;
                    $temp_variant['name'] = $new_product['name'];
                    $temp_variant['type'] = 'simple';
                }
                if( is_numeric( $variation->sale_price ) ){
                    $temp_variant['price'] = round( floatval( $variation->sale_price), 2);
                }
                if( is_numeric( $variation->market_price ) ){
                    $temp_variant['regular_price'] = round( floatval( $variation->sale_price), 2);
                }
                if( is_numeric( $variation->sale_price ) ){
                    $temp_variant['final_price'] = round( floatval( $variation->sale_price), 2);
                }
                $temp_variant['manage_stock'] = true;
                $temp_variant['stock_quantity'] = $variation->quantity;
                if( $varient_id && $varient_id > 0 ){
                    // Update Data for existing Variend Here.
                }else{
                    $temp_variant['weight'] = round( floatval( $variation->weight), 2);
                
                    if( isset( $variation->attributes ) && !empty( $variation->attributes ) ){
                        foreach ( $variation->attributes as $attribute ) {
                            $temp_attribute_name = isset( $attribute->name ) ?  $attribute->name->$default_lang : '';
                            $temp_attribute_value = isset( $attribute->option ) ?  $attribute->option->$default_lang : '';

                            // continue if no attribute name found.
                            if( $temp_attribute_name == '' ){
                                continue;
                            }

                            $temp_var_attribute = array();
                            $temp_var_attribute['name'] = $temp_attribute_name;
                            $temp_var_attribute['value'] = array( $temp_attribute_value );
                            //$temp_var_attribute['name'] = $attribute->name;
                            //$temp_var_attribute['value'] = $attribute->option;
                            $temp_var_attribute['taxonomy'] = true;
                            $temp_variant['raw_attributes'][] = $temp_var_attribute;

                            // Add attribute name to $var_attributes for make it taxonomy.
                            $var_attributes[] = $temp_attribute_name;

                            if( isset( $attributes[ $temp_attribute_name ] ) ){
                                if( !in_array( $temp_attribute_value, $attributes[ $temp_attribute_name ] ) ){
                                    $attributes[ $temp_attribute_name ][] = $temp_attribute_value;
                                }
                            }else{
                                $attributes[ $temp_attribute_name ][] = $temp_attribute_value;
                            }
                        }
                    }
                }
                $variations[] = $temp_variant;
            }
            // echo "<pre>";
            // print_r($variations);
            // die();
        }
        if( !empty( $attributes ) ){
            foreach ( $attributes as $name => $value ) {
                $temp_raw = array();
                $temp_raw['name'] = $name;
                $temp_raw['value'] = $value;
                $temp_raw['visible'] = true;
                if( in_array( $name, $var_attributes ) ){
                    $temp_raw['taxonomy'] = true;
                    $temp_raw['default'] = isset( $value[0] ) ? $value[0] : '';
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
    public function getAdminLanguage(){
        $language = $this->authSession->getUser()->getInterfaceLocale();
        $language = explode(',', $language);
        if(array_key_exists(0,$language)){
            $language_code = explode('_', $language[0]);
            if($language_code[0] == 'ar' || $language_code[0] == 'en' || $language_code[0] == 'tr'){
                $language_identifier = $language_code[0];
            }else{
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
    public function getConfigData($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::PATH_KNAWAT_DEFAULT.$path, $storeScope);
    }

    public function getAttrSetId($attrSetName)
    {
        $attributeSet = $this->_attributeSetCollection->create()->addFieldToSelect( '*' )->addFieldToFilter(
                        'attribute_set_name',
                        $attrSetName
                    );
        $attributeSetId = 0;
        foreach($attributeSet as $attr):
            $attributeSetId = $attr->getAttributeSetId();
        endforeach;
        return $attributeSetId;
    }

    public function getAttributeId($attributeCode)
    {
        return $this->_eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode );
    }

    /**
     * Create Attributes for given attribute Set.
     *
     * @param Array $attributes
     * @param String $attrSetId
     * @return void
     */
    public function createUpdateAttributes( $attributes, $attrSetId ){
        if( empty($attributes)){
            return;
        }
        $formattedAttributes = array();
        foreach( $attributes as $attribute ){
            if( !isset($attribute['name']) || empty($attribute['name'])){
                continue;
            }
            $attributeCode = $this->generateAttributeCode($attribute['name']);
            $attributeId = $this->getAttributeId( $attributeCode );
            $formattedAttributes[$attribute['name']] = array();
            if( empty($attributeId ) ){
                $new_attribute = $this->_objectManager->create( \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class );
                $categorySetup = $this->_objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
                $entityTypeId = $categorySetup->getEntityTypeId('catalog_product');
                if( isset($attribute['taxonomy']) && $attribute['taxonomy'] == '1'){
                    // Create Dropdown Attribute
                    $attributeValues = array();
                    if( isset( $attribute['value'] ) && !empty( $attribute['value'] ) ){
                        foreach( $attribute['value'] as $value ){
                            $attributeValues[$this->generateAttributeValueKey($value)] = array( $value );
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
                            'backend_type'                  => 'varchar',
                            'backend_model'                 => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                            'option'                        => array( 'value' => $attributeValues ),
                        ]
                    );
                } else{
                    // Create Normal Attribute
                    $new_attribute->setData(
                        [
                            'attribute_code'                => $attributeCode,
                            'entity_type_id'                => $entityTypeId,
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
                            'backend_type'                  => 'text',
                        ]
                    );
                }
                $new_attribute->save();
                /* Assign attribute to attribute set */
                $categorySetup->addAttributeToGroup('catalog_product', 'Knawat', 'General', $new_attribute->getId());

                $attributeId = $new_attribute->getId();
                $attributeCode = $new_attribute->getAttributeCode();
                $productAttributeRepository = $this->_objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
                $options = $productAttributeRepository->get($attributeCode)->getOptions();
                foreach ($options as $option){
                    if( !empty( $option->getLabel() )){
                        $existingOptions[$option->getLabel()] = $option->getValue();
                    }
                }

                $formattedAttributes[$attribute['name']]['attr_id'] = $attributeId;
                $formattedAttributes[$attribute['name']]['attr_code'] = $attributeCode;
                $formattedAttributes[$attribute['name']]['attr_options'] = $existingOptions;
            } else {
                $existingOptions = array();
                $existingAttribute = $this->_objectManager->create( \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class )->load($attributeId);
                $attributeCode = $existingAttribute->getAttributeCode();
                $productAttributeRepository = $this->_objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
                $options = $productAttributeRepository->get($attributeCode)->getOptions();
                foreach ($options as $option){
                    if( !empty( $option->getLabel() )){
                        $existingOptions[$option->getLabel()] = $option->getValue();
                    }
                }
                $formattedAttributes[$attribute['name']]['attr_id'] = $attributeId;
                $formattedAttributes[$attribute['name']]['attr_code'] = $attributeCode;
                $formattedAttributes[$attribute['name']]['attr_options'] = $existingOptions;
            }
            return $formattedAttributes;
        }
    }

    /**
     * Generate attribute code from label
     *
     * @param string $label
     * @return string
     */
    protected function generateAttributeCode($label)
    {
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
     * @return array websites
     */
    protected function getWebsites(){
        $_storeRepository = $this->_objectManager->create('Magento\Store\Model\StoreRepository');
        $stores = $_storeRepository->getList();
        $websites = array();
        foreach ($stores as $store) {
            if( $store["code"] == 'admin' ){
                continue;
            }
            $websiteId = $store["website_id"];
            $storeId = $store["store_id"];
            $store = array(
                'code' => $store["code"],
                'lang' => 'en',
            );
            $websites[$websiteId][$storeId] = $store;
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
}
