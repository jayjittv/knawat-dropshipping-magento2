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

    protected $productModel;
    protected $authSession;
    protected $pricingHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $productUrl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

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
    protected $attributeSetCollection;

    /**
     * Translit Name to URL key.
     *
     * @var \Magento\Framework\Filter\TranslitUrl
     */
    protected $translitUrl;

    /**
     * @var \Knawat\Dropshipping\Helper\General
     */
    protected $generalHelper;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterfaceFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Import Type
     *
     * @var string
     */
    protected $importType = 'full';

    /**
     * Logger for error logs
     *
     * @var string
     */
    protected $logger;

    /**
     * @var array ImportData
     */
    protected $data;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * knawat default configuration path value
     */
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Filter\TranslitUrl $translitUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Knawat\MPFactory $mpFactory
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
        \Magento\Framework\Filter\TranslitUrl $translitUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Knawat\Dropshipping\Helper\General $generalHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Knawat\MPFactory $mpFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($context);
        $this->productModel = $productModel;
        $this->authSession = $authSession;
        $this->pricingHelper = $pricingHelper;
        $this->scopeConfig = $scopeConfig;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->eavAttribute = $eavAttribute;
        $this->objectManager = $objectManager;
        $this->productUrl = $productUrl;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->translitUrl = $translitUrl;
        $this->storeManager = $storeManager;
        $this->generalHelper = $generalHelper;
        $this->stockRegistry = $stockRegistry;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;

        // Import parameters.
        $this->mpFactory = $mpFactory;
        $this->logger = $generalHelper->getLogger();
        $this->websites = $generalHelper->getWebsites();
        $this->defaultLanguage = $generalHelper->getDefaultLanguage();
        $this->productMetadata = $productMetadata;
    }

    public function import($importType = 'full', $params = [])
    {
        $this->importType = $importType;
        $default_args = [
            // 'import_id'         => 0,  // Import_ID
            'limit'             => 25, // Limit for Fetch Products
            'page'              => 1, // Page Number
            'product_index'     => -1, // product index needed incase of memory issuee or timeout
            // 'force_update'      => false, // Whether to force update existing items.
            'prevent_timeouts'  => true, // Check memory and time usage and abort if reaching limit.
            'is_complete'       => false, // Is Import Complete?
            'products_total'    => -1,
            'imported'          => 0,
            'failed'            => 0,
            'updated'           => 0,
            'skipped'           => 0
        ];
        $this->params = array_merge($default_args, $params);
        $defaultLanguage = $this->defaultLanguage;
        $mp = $this->generalHelper->createMP();
        $websites = $this->generalHelper->getWebsites(false);
        if (empty($websites)) {
            return ['status' => 'fail', 'message' => __('Websites are not enabled for import.')];
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
                $lastUpdated = $this->generalHelper->getConfigDirect('knawat_last_imported', true);
                $sortData = array(
                  'sort' => array('field'=>'updated','order'=>'asc')
                );
                if (empty($lastUpdated)) {
                    $lastUpdated = 0;
                }
                $this->data = $mp->getProducts($this->params['limit'], $this->params['page'], $lastUpdated,$sortData);
                break;

            case 'single':
                $sku = $this->params['sku'];
                if (empty($sku)) {
                    return ['status' => 'fail', 'message' => __('Please provide product sku.')];
                }
                $this->data = $mp->getProductBySku($sku);
                break;

            default:
                $this->data = null;
                break;
        }

        if (isset($this->data->code) && isset($this->data->message)) {
            return ['status' => 'fail', 'message' => $this->data->name.': '.$this->data->message];
        }

        // Check for Products
        $response = $this->data;
        if (isset($response->products) || ('single' === $this->importType && isset($response->product))) {
            $products = [];
            if ('single' === $this->importType) {
                if (isset($response->product->status) && 'failed' == $response->product->status) {
                    $error_message = isset($response->product->message) ? $response->product->message : __('Something went wrong during get data from Knawat MP API. Please try again later.');
                    return ['status' => 'fail', 'message' => $error_message];
                }
                $products[] = $response->product;
            } else {
                $products = $response->products;
            }
            // Handle errors
            if (isset($products->code) || !is_array($products)) {
                return ['status' => 'fail', 'message' => __('Something went wrong during get data from Knawat MP API. Please try again later.')];
            }

            // Update Product totals.
            $this->params['products_total'] = count($products);
            if (empty($products)) {
                $this->params['is_complete'] = true;
                return $data;
            }
            // General Variables
            $attributeSetId = $this->getAttrSetId('Knawat');
            $defaultCategoryId = $this->storeManager->getStore()->getRootCategoryId();
            $savedAttributes = [];
            $date='';
            foreach ($products as $index => $product) {
                if ($index <= $this->params['product_index']) {
                    continue;
                }

                $formated_data = $this->getFormattedProducts($product);
                if (empty($formated_data)) {
                    // Update product index
                    $this->params['product_index'] = $index;
                    continue;
                }
                // Prevent new import for 0 qty products.
                $totalQty = 0;
                $variations = isset($formated_data['variations']) ? $formated_data['variations'] : [];
                if (!empty($variations)) {
                    foreach ($variations as $vars) {
                        $totalQty += isset($vars['stock_quantity']) ? $vars['stock_quantity'] : 0;
                    }
                }
                if(!empty($formated_data['updated_time'])){
                    $this->params['last_updated'] = $formated_data['updated_time'];
                }
                if(!empty($product->updated)){
                    $date = $product->updated;
                }
                if (!isset($formated_data['id']) || empty($formated_data['id'])) {
                    if ($totalQty == 0) {
                        $data['skipped'][] = $formated_data['sku'];
                        // Update product index
                        $this->params['product_index'] = $index;
                        continue;
                        // @todo  Log needed here.
                    }
                    if (isset($formated_data['raw_attributes'])) {
                        // Create and Setup Attributes.
                        $savedAttributes = $this->createUpdateAttributes(
                            $formated_data['raw_attributes'],
                            $attributeSetId
                        );
                    }
                }
                // Create Update Product Variations.
                $associatedProductIds = [];
                $existingAssociatedProductIds = [];
                $associatedProductSkus = [];
                $productResource = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product');
                try {
                    foreach ($variations as $variation) {
                        if (isset($variation['id']) && !empty($variation['id']) && $variation['id'] > 0) {
                            // update Exising Product Variation
                            $var_product = $this->objectManager->create('\Magento\Catalog\Model\Product')->load($variation['id']);
                            $var_product->setPrice($variation['price']); // price of product
                            $var_product->setCost($variation['cost']); // cost of product
                            $productResource->saveAttribute($var_product, 'price');
                            $productResource->saveAttribute($var_product, 'cost');
                            // Update stock
                            $stockItem = $this->stockRegistry->getStockItemBySku($var_product->getSku());
                            $stockItem->setQty($variation['stock_quantity']);
                            $stockItem->setIsInStock((bool) ($variation['stock_quantity'] > 0) ? 1 : 0);
                            $this->stockRegistry->updateStockItemBySku($var_product->getSku(), $stockItem);

                            $existingAssociatedProductIds[] = $var_product->getId();
                        } else {
                            if (empty($savedAttributes)) {
                                if (isset($formated_data['raw_attributes'])) {
                                    // Create and Setup Attributes.
                                    $savedAttributes = $this->createUpdateAttributes(
                                        $formated_data['raw_attributes'],
                                        $attributeSetId
                                    );
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

                            $var_product = $this->productFactory->create();
                            $var_product->setSku($variation['sku']);
                            $var_product->setName($variation['name']);
                            $var_product->setTypeId('simple');
                            $var_product->setPrice($variation['price']);
                            $var_product->setCost($variation['cost']);
                            $var_product->setStatus(
                                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                            );
                            $var_product->setVisibility(
                                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
                            );
                            $var_product->setWebsiteIds(array_keys($websites));
                            $var_product->setCategoryIds([$defaultCategoryId]);
                            $var_product->setWeight($variation['weight']);
                            $var_product->setData('is_knawat', 1);
                            foreach ($attributeValues as $attributeKeyCode => $attributeValue) {
                                $var_product->setData($attributeKeyCode, $attributeValue);
                            }
                            $var_product->setAttributeSetId($attributeSetId);
                            $var_product->setStockData(
                                [
                                    'use_config_manage_stock' => 0,
                                    'manage_stock' => $variation['manage_stock'],
                                    'is_in_stock'  => ($variation['stock_quantity'] > 0) ? 1 : 0,
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
                        // Update stock
                        $stockItem = $this->stockRegistry->getStockItemBySku($formated_data['sku']);
                        $stockItem->setIsInStock((bool) ($totalQty > 0) ? 1 : 0);
                        $this->stockRegistry->updateStockItemBySku($formated_data['sku'], $stockItem);

                        $data['updated'][] = $formated_data['id'];
                        if (!empty($associatedProductSkus)) {
                            $linkManagement = $this->objectManager->create(\Magento\ConfigurableProduct\Model\LinkManagement::class);
                            foreach ($associatedProductSkus as $associatedProductSku) {
                                try {
                                    $linkManagement->addChild($main_product->getSku(), $associatedProductSku);
                                } catch (\Exception $e) {
                                    // @todo logger here.
                                }
                            }
                        }
                    } else {
                        // Main Product with versions.
                        $version = $this->productMetadata->getVersion();
                        $versionCompare = version_compare($version, "2.2");
                        if ($versionCompare == -1) {
                            /*code for version 2.1.x*/
                        $main_product = $this->productFactory->create();
                        $main_product->setSku($formated_data['sku']);
                        $main_product->setName($formated_data['name']);
                        $main_product->setUrlKey(
                            $this->translitUrl->filter($formated_data['name'].' '.$formated_data['sku'])
                        );
                        $main_product->setDescription($formated_data['description']);
                        $main_product->setAttributeSetId($attributeSetId);
                        $main_product->setStatus(
                            \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                        );
                        $main_product->setData('is_knawat', 1);
                        $main_product->setCategoryIds([$defaultCategoryId]);

                        $configurableAttributesIds = [];
                        foreach ($savedAttributes as $savedAttribute) {
                            if (isset($savedAttribute['attr_id'])) {
                                $configurableAttributesIds[] = $savedAttribute['attr_id'];
                            }
                        }
                        // Super Attribute Ids Used To Create Configurable Product.
                        $configurableAttributesIds = array_unique($configurableAttributesIds);

                        $main_product->setAffectConfigurableProductAttributes($attributeSetId);
                        $main_product->save();
                        $productId = $main_product->getId();
                        $main_product = $this->productRepository->getById($productId);
                        $position = 0;
                        $attributeModel = $this->objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
                        foreach ($configurableAttributesIds as $attributeId) {
                            $attribData = ['attribute_id' => $attributeId, 'product_id' => $productId, 'position' => $position];
                            $position++;
                            $attributeModel->setData($attribData)->save();
                        }
                        $main_product->setTypeId('configurable');
                        $main_product->setWebsiteIds(array_keys($websites));
                        $main_product->getTypeInstance()->setUsedProductAttributeIds(
                            $configurableAttributesIds,
                            $main_product
                        );
                        $main_product->setNewVariationsAttributeSetId($attributeSetId); // Setting Attribute Set Id
                        $main_product->setAssociatedProductIds($associatedProductIds); // Setting Associated Products
                        $main_product->setStockData(
                            [
                                    'use_config_manage_stock' => 0,
                                    'manage_stock' => 1,
                                    'is_in_stock' => ($totalQty > 0) ? 1 : 0
                            ]
                        );
                        // Set Existing Associated Products.
                        if (!empty($existingAssociatedProductIds)) {
                            $existingAssociatedProductIds = array_merge(
                                $existingAssociatedProductIds,
                                $associatedProductIds
                            );
                            $main_product->setAssociatedProductIds($existingAssociatedProductIds);
                        }

                        $main_product->setCanSaveConfigurableAttributes(true);
                        if (isset($savedAttributes['info_attribute']) && !empty($savedAttributes['info_attribute'])) {
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                $infoAttrib = current($infoAttribute);
                                $infoAttrib = isset($infoAttrib->$defaultLanguage) ? $infoAttrib->$defaultLanguage : '';
                                $main_product->setData($infoKey, $infoAttrib);
                            }
                        }
                        
                        if (isset($formated_data['images']) && !empty($formated_data['images'])) {
                            $this->importImages($main_product, $formated_data['images']);
                        }
                        
                        $main_product->save();
                        $productId = $main_product->getId();

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

                        if (isset($savedAttributes['info_attribute']) && !empty($savedAttributes['info_attribute'])) {
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                foreach ($websites as $webKey => $website) {
                                    foreach ($website as $storeKey => $store) {
                                        $storeLang = isset($store['lang']) ? $store['lang'] : $defaultLanguage;
                                        $infoAttrib = current($infoAttribute);
                                        $infoAttrib = isset($infoAttrib->$storeLang) ? $infoAttrib->$storeLang : '';
                                        $main_product->addAttributeUpdate($infoKey, $infoAttrib, $storeKey);
                                    }
                                }
                            }
                        }


                        $data['imported'][] = $productId;
                            /*code for version 2.1.x*/    
                            } else {
                            /*code for version 2.2.x*/
                        $main_product = $this->productFactory->create();
                        $main_product->setSku($formated_data['sku']);
                        $main_product->setName($formated_data['name']);
                        $main_product->setUrlKey(
                            $this->translitUrl->filter($formated_data['name'].' '.$formated_data['sku'])
                        );
                        $main_product->setDescription($formated_data['description']);
                        $main_product->setAttributeSetId($attributeSetId);
                        $main_product->setStatus(
                            \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                        );
                        $main_product->setData('is_knawat', 1);
                        $main_product->setTypeId('configurable');
                        $main_product->setWebsiteIds(array_keys($websites));
                        $main_product->setCategoryIds([$defaultCategoryId]);
                        $main_product->setStockData(
                            [
                                'use_config_manage_stock' => 0,
                                'manage_stock' => 1,
                                'is_in_stock' => ($totalQty > 0) ? 1 : 0
                            ]
                        );

                        $configurableAttributesIds = [];
                        foreach ($savedAttributes as $savedAttribute) {
                            if (isset($savedAttribute['attr_id'])) {
                                $configurableAttributesIds[] = $savedAttribute['attr_id'];
                            }
                        }
                        // Super Attribute Ids Used To Create Configurable Product.
                        $configurableAttributesIds = array_unique($configurableAttributesIds);

                        $main_product->setAffectConfigurableProductAttributes($attributeSetId);
                        $main_product->getTypeInstance()->setUsedProductAttributeIds(
                            $configurableAttributesIds,
                            $main_product
                        );
                        $main_product->setNewVariationsAttributeSetId($attributeSetId); // Setting Attribute Set Id
                        $main_product->setAssociatedProductIds($associatedProductIds); // Setting Associated Products

                        // Set Existing Associated Products.
                        if (!empty($existingAssociatedProductIds)) {
                            $existingAssociatedProductIds = array_merge(
                                $existingAssociatedProductIds,
                                $associatedProductIds
                            );
                            $main_product->setAssociatedProductIds($existingAssociatedProductIds);
                        }

                        $main_product->setCanSaveConfigurableAttributes(true);
                        if (isset($savedAttributes['info_attribute']) && !empty($savedAttributes['info_attribute'])) {
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                $infoAttrib = current($infoAttribute);
                                $infoAttrib = isset($infoAttrib->$defaultLanguage) ? $infoAttrib->$defaultLanguage : '';
                                $main_product->setData($infoKey, $infoAttrib);
                            }
                        }

                        if (isset($formated_data['images']) && !empty($formated_data['images'])) {
                            $this->importImages($main_product, $formated_data['images']);
                        }
                        $main_product->save();
                        $productId = $main_product->getId();

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

                        if (isset($savedAttributes['info_attribute']) && !empty($savedAttributes['info_attribute'])) {
                            foreach ($savedAttributes['info_attribute'] as $infoKey => $infoAttribute) {
                                foreach ($websites as $webKey => $website) {
                                    foreach ($website as $storeKey => $store) {
                                        $storeLang = isset($store['lang']) ? $store['lang'] : $defaultLanguage;
                                        $infoAttrib = current($infoAttribute);
                                        $infoAttrib = isset($infoAttrib->$storeLang) ? $infoAttrib->$storeLang : '';
                                        $main_product->addAttributeUpdate($infoKey, $infoAttrib, $storeKey);
                                    }
                                }
                            }
                        }

                        $position = 0;
                        $attributeModel = $this->objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
                        foreach ($configurableAttributesIds as $attributeId) {
                            $attribData = ['attribute_id' => $attributeId, 'product_id' => $productId, 'position' => $position];
                            $position++;
                            $attributeModel->setData($attribData)->save();
                        }
                        $data['imported'][] = $productId;
                            /*code for version 2.2.x*/    
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->info("[PRODUCT_IMPORT][ERROR] ".$e->getMessage());
                    if (isset($formated_data['id'])) {
                        $data['failed'][] = $formated_data['id'];
                    } elseif (isset($formated_data['sku'])) {
                        $data['failed'][] = $formated_data['sku'];
                    }
                }
                // Update product index
                $this->params['product_index'] = $index;

                // Prevent Timeout and Memory exceed.
                if ($this->params['prevent_timeouts'] && ($this->generalHelper->timeExceeded($this->start_time) || $this->generalHelper->memoryExceeded())) {
                    break;
                }
            }

            if ($this->params['products_total'] === 0) {
                $this->params['is_complete'] = true;
            } else {
                $this->params['is_complete'] = false;
            }
            $datetime = new \DateTime($date);
            $lastUpdateTime = (int) ($datetime->getTimestamp().$datetime->format('u')/ 1000);
            if(!empty($date) && $lastUpdated != $lastUpdateTime){
                //update product import date           
                $lastImportPath = self::PATH_KNAWAT_DEFAULT.'knawat_last_imported';
                $this->generalHelper->setConfig($lastImportPath, $lastUpdateTime);
                    $this->params['page'] = 1;
                    $this->params['product_index'] = -1;
            } else if( $this->params['products_total'] == ( $this->params['product_index'] + 1 ) ){
                $this->params['page'] += 1;
            }
            return $data;
        } else {
            return ['status' => 'fail', 'message' => __('Something went wrong during get data from Knawat MP API. Please try again later.')];
        }
    }

    public function getFormattedProducts($product)
    {
        $new_product = [];
        try {
            if (empty($product)) {
                return $product;
            }
            $defaultLanguage = $this->defaultLanguage;
            $weightMultiplier = $this->generalHelper->getWeightMultiplier();
            if (empty($weightMultiplier)) {
                $weightMultiplier = 1;
            }
            $attributes = [];

            $sku = $product->sku;
            $updated = $product->updated;
            if(isset($updated)){
                $new_product['updated_time'] = $updated;
            }
            $product_id = $this->getProductBySku($sku);
            if ($product_id) {
                $new_product['id'] = $product_id;
                $new_product['sku'] = $product->sku;
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
                $new_product['name'] = $new_product['description'] = '';
                if (isset($product->name->$defaultLanguage)) {
                    $new_product['name'] = $product->name->$defaultLanguage;
                }
                if (isset($product->description->$defaultLanguage)) {
                    $new_product['description'] = $product->description->$defaultLanguage;
                }
                $new_product['name_i18n'] = isset($product->name) ? $product->name : '';
                $new_product['description_i18n'] = isset($product->description) ? $product->description : '';

                if (isset($product->images) && !empty($product->images)) {
                    $new_product['images'] = $product->images;
                }

                if (isset($product->attributes) && !empty($product->attributes)) {
                    foreach ($product->attributes as $attribute) {
                        if (!isset($attribute->name) || empty($attribute->name)) {
                            continue;
                        }

                        $defaultCode = isset($attribute->name->en) ? $attribute->name->en : '';
                        if (empty($defaultCode)) {
                            $defaultCode = isset($attribute->name->tr) ? $attribute->name->tr : '';
                        }
                        $attributeName = isset($attribute->name->$defaultLanguage) ? $attribute->name->$defaultLanguage : '';

                        // Continue if no attribute name found.
                        if (empty($attributeName) || empty($defaultCode)) {
                            continue;
                        }

                        $attributeOptions = [];
                        $attributeFormated = '';
                        if (isset($attribute->options) && !empty($attribute->options)) {
                            foreach ($attribute->options as $attributeValue) {
                                $attributeFormated = isset($attributeValue->$defaultLanguage) ? $attributeValue->$defaultLanguage : '';
                                /* if (empty($attributeFormated) && isset($attributeValue->tr) ) {
                                    $attributeFormated = $attributeValue->tr;
                                } */
                                if (!array_key_exists($attributeFormated, $attributeOptions) && !empty($attributeFormated)) {
                                    $attributeOptions[$attributeFormated] = $attributeValue;
                                }
                            }
                        }
                        if (!empty($attributeOptions) && is_array($attributeOptions)) {
                            $attributeOptions = [
                                'name_i18n' => $attribute->name,
                                'value'     => $attributeOptions,
                                'attr_code' => $defaultCode
                            ];
                        }

                        if (!empty($attributeOptions)) {
                            if (!isset($attributes[$attributeName])) {
                                $attributes[$attributeName] = $attributeOptions;
                                /* $attributes[ $attributeName ] = array_unique(
                                    array_merge($attributes[ $attributeName ], $attributeOptions)
                                ); */
                            }
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
                    if (isset($variation->cost_price) && is_numeric($variation->cost_price)) {
                        $temp_variant['cost'] = $temp_variant['cost'] = round(floatval($variation->cost_price), 2);
                    }
                    if (isset($variation->market_price) && is_numeric($variation->market_price)) {
                        $temp_variant['regular_price'] = round(floatval($variation->market_price), 2);
                    }
                    $temp_variant['manage_stock'] = true;
                    $temp_variant['stock_quantity'] = isset($variation->quantity) ? $variation->quantity : 0;
                    $temp_variant['weight'] = isset($variation->weight) ? round(floatval($variation->weight * $weightMultiplier), 2) : 0;
                    if ($varient_id && $varient_id > 0 && $product_id) {
                        // Update Data for existing Variend Here.
                    } else {
                        if (isset($variation->attributes) && !empty($variation->attributes)) {
                            foreach ($variation->attributes as $attribute) {
                                // continue if no attribute name found.
                                if (!isset($attribute->name) || empty($attribute->name) || !isset($attribute->option)) {
                                    continue;
                                }
                                $defaultCode = isset($attribute->name->en) ? $attribute->name->en : '';
                                if (empty($defaultCode)) {
                                    $defaultCode = isset($attribute->name->tr) ? $attribute->name->tr : '';
                                }
                                $defaultAttributeName = isset($attribute->name->$defaultLanguage) ? $attribute->name->$defaultLanguage : '';
                                // Take a chance for not translated attributes
                                if (empty($defaultAttributeName) && isset($attribute->name->tr)) {
                                    $defaultAttributeName = $attribute->name->tr;
                                }
                                $defaultAttributeValue = isset($attribute->option->$defaultLanguage) ? $attribute->option->$defaultLanguage : '';
                                // Take a chance for not translated attributes
                                if (empty($defaultAttributeValue) && isset($attribute->option->tr)) {
                                    $defaultAttributeValue = $attribute->option->tr;
                                }
                                if (empty($defaultAttributeName) || empty($defaultAttributeValue)) {
                                    continue;
                                }

                                $temp_variant['raw_attributes'][$defaultAttributeName] = $defaultAttributeValue;

                                // Add attribute name to $var_attributes for make it taxonomy.
                                $var_attributes[] = $defaultAttributeName;

                                if (isset($attributes[$defaultAttributeName])) {
                                    if (!array_key_exists($defaultAttributeValue, $attributes[$defaultAttributeName]['value'])) {
                                        $attributes[$defaultAttributeName]['value'][$defaultAttributeValue] = $attribute->option;
                                    }
                                } else {
                                    $attributes[$defaultAttributeName]['name_i18n'] = $attribute->name;
                                    $attributes[$defaultAttributeName]['value'][$defaultAttributeValue] = $attribute->option;
                                    $attributes[$defaultAttributeName]['attr_code'] = $defaultCode;
                                }
                            }
                            if (isset($temp_variant['raw_attributes']) && !empty($temp_variant['raw_attributes'])) {
                                if (isset($temp_variant['name'])) {
                                    $temp_variant['name'] .= '-'.implode('-', $temp_variant['raw_attributes']);
                                }
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
        } catch (\Exception $e) {
            $this->logger->info("[FORMAT_PRODUCT][ERROR] ".$e->getMessage());
        }
        return $new_product;
    }

    /**
     * Get ProductModel by SKU
     *
     * @param string $sku
     * @return object $this->productModel
     */
    public function getProductBySku($sku)
    {
        return $product = $this->productModel->getIdBySku($sku);
    }

    public function getAttrSetId($attrSetName)
    {
        $attributeSet = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter(
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
        return $this->eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
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
        try {
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
                $websites = $this->websites;
                if (empty($attributeId)) {
                    $new_attribute = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
                    $categorySetup = $this->objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
                    $entityTypeId = $categorySetup->getEntityTypeId('catalog_product');
                    $storeLabels = [];
                    foreach ($websites as $webKey => $website) {
                        foreach ($website as $storeKey => $store) {
                            $storeLang = $store['lang'];
                            $storeLabels[$storeKey] = isset($attribute['value']['name_i18n']->$storeLang) ? $attribute['value']['name_i18n']->$storeLang : $attribute['name'];
                        }
                    }
                    if (empty($storeLabels)) {
                        $storeLabels = [$attribute['name']];
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
                                    $storeValue = [$valueKey];
                                }
                                $attributeValues[$attributeCode.'_'.$this->generateAttributeValueKey($valueKey)] = $storeValue;
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
                                'option'                        => ['value' => $attributeValues],
                            ]
                        );
                        $new_attribute->save();
                        /* Assign attribute to attribute set */
                        $categorySetup->addAttributeToGroup('catalog_product', 'Knawat', 'General', $new_attribute->getId());

                        $attributeId = $new_attribute->getId();
                        $attributeCode = $new_attribute->getAttributeCode();
                        $productAttributeRepository = $this->objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
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
                        $attValues = [];
                        if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                            $attValues = $attribute['value']['value'];
                        }
                        $formattedAttributes['info_attribute'][$attributeCode] = $attValues;
                    }
                } else {
                    if (isset($attribute['taxonomy']) && $attribute['taxonomy'] == '1') {
                        $existingOptions = [];
                        $existingAttribute = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)->load($attributeId);
                        $attributeCode = $existingAttribute->getAttributeCode();
                        $productAttributeRepository = $this->objectManager->create(\Magento\Catalog\Model\Product\Attribute\Repository::class);
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
                                        $storeOption = [$valueKey];
                                    }

                                    $_option = $this->objectManager->create(\Magento\Eav\Model\Entity\Attribute\Option::class);
                                    $_attributeOptionManagement = $this->objectManager->create(\Magento\Eav\Api\AttributeOptionManagementInterface::class);
                                    $_attributeOptionLabel = $this->objectManager->create(\Magento\Eav\Api\Data\AttributeOptionLabelInterface::class);

                                    $storeLabels = [];
                                    foreach ($storeOption as $sKey => $sOption) {
                                        $_attributeOptionLabel2 = $this->objectManager->create(\Magento\Eav\Api\Data\AttributeOptionLabelInterface::class);
                                        $_attributeOptionLabel2->setStoreId($sKey);
                                        $_attributeOptionLabel2->setLabel($sOption);
                                        $storeLabels[$sKey] = $_attributeOptionLabel2;
                                    }
                                    $_attributeOptionLabel->setStoreId(0);
                                    $_attributeOptionLabel->setLabel($valueKey);
                                    /*version compare for set label*/
                                    $version = $this->productMetadata->getVersion();
                                    $versionCompare = version_compare($version, "2.3");
                                    if(version_compare($version, "2.4.1",'>=') == 1){
                                        $_option->setLabel((string)$valueKey);
                                    }else if ($versionCompare == 1) {
                                         $_option->setLabel($valueKey);
                                     }else{
                                         $_option->setLabel($_attributeOptionLabel);
                                     }

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
                    } else {
                        $attValues = [];
                        if (isset($attribute['value']['value']) && !empty($attribute['value']['value'])) {
                            $attValues = $attribute['value']['value'];
                        }
                        $formattedAttributes['info_attribute'][$attributeCode] = $attValues;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info("[CREATE_UPDATE_ATTRIBUTE][ERROR] ".$e->getMessage());
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
        if ($isNormal) {
            $label = 'k_'.$label;
        }
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->productUrl->formatUrlKey($label)
            ),
            0,
            25
        );
        $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,24}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = ($code ?: substr(hash('sha256', time()), 0, 8));
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
                $this->productUrl->formatUrlKey($value)
            ),
            0,
            30
        );
        $validatorAttrValue = new \Zend_Validate_Regex(['pattern' => '/^[a-z_0-9]+$/']);
        if (!$validatorAttrValue->isValid($valueKey)) {
            $valueKey = 'att_val_'.($valueKey ?: substr(hash('sha256', time()), 0, 8));
        }
        return $valueKey;
    }

    /**
     * Get Import Parameters
     *
     * @param string $value Field value.
     * @return float|string
     */
    public function getImportParams()
    {
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
    public function importImages($product, $imageUrls)
    {
        if (empty($imageUrls)) {
            return false;
        }
        // Temporory Directory for Image.
        $tmpDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA).DIRECTORY_SEPARATOR.'tmp';
        // Create folder if it is not exists.
        $this->file->checkAndCreateFolder($tmpDir);

        foreach ($imageUrls as $index => $imageUrl) {
            // File Path for download image.
           $tempImgName =  random_int(999, 9999999) . baseName($imageUrl);
            $newFileName = $tmpDir . DIRECTORY_SEPARATOR . $tempImgName;
            if(strlen($tempImgName)>=90){
                $cutLength = (strlen($tempImgName) - 90) +10;
                $newFileName = $tmpDir . DIRECTORY_SEPARATOR . substr($tempImgName, $cutLength);
            }
            $newFileName = strtok($newFileName, "?");
            $imageType = null;
            if ($index == 0) {
                $imageType = ['image', 'small_image', 'thumbnail'];
            }
            // Download file from remote URL and Add to Temp Directory/
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $raw_image_data = curl_exec($ch);
            curl_close($ch);
            try {
                $image = fopen($newFileName, 'w');
                fwrite($image, $raw_image_data);
                fclose($image);
                // Check file exists or not.
                if (file_exists($newFileName)) {
                    // Check size of file.
                    $size = filesize($newFileName);
                    if ($size > 0) {
                        $product->addImageToMediaGallery($newFileName, $imageType, false, false);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
