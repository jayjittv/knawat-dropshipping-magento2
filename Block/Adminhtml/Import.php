<?php

namespace Knawat\Dropshipping\Block\Adminhtml;

/**
 * Class Import
 * @package Knawat\Dropshipping\Block\Adminhtml
 */
class Import extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configModel;
    
    /**
     * @var
     */
    protected $configHelper;

    /**
     *knawat default configuration path value
     */
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Settings constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Knawat\Dropshipping\Helper\ManageConfig $confighelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configModel = $configModel;
        $this->confighelper = $confighelper;
        $this->productMetadata = $productMetadata;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfigData($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::PATH_KNAWAT_DEFAULT.$path, $storeScope);
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImportStatus()
    {
        $configConnection = $this->configModel->getConnection();
        $identifier = 'kdropship_import';
        $select = $configConnection->select()->from($this->configModel->getMainTable())->where('path=?', self::PATH_KNAWAT_DEFAULT.$identifier);
        $configData = $configConnection->fetchRow($select);
        if (!empty($configData) && isset($configData['value'])) {
            $importData = $configData['value'];
        }

        if (!empty($importData)) {
            $batch = $this->serializer->unserialize($importData);
            return $batch;
        }
        return false;
    }

    public function getKnawatConnection(){
            if($this->confighelper->getToken()){
            return true;
            } else{
            return false;
            }
    }

    public function isVersionTwo(){
        $version = $this->productMetadata->getVersion();
        $versionCompare = version_compare($version,  "2.2");
        if($versionCompare == -1){
            return true;
            } else{
                return false;
        }
    }
}
