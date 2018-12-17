<?php

namespace Knawat\Dropshipping\Helper;

use Knawat\MP;

/**
 * Class ManageConfig
 * @package Knawat\Dropshipping\Helper
 */
class General extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     *knawat default configuration path value
     */
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var
     */
    protected $mpApi;

    /**
     * @var \Knawat\MPFactory
     */
    protected $mpFactory;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $configInterface;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configModel;

    /**
     * ManageConfig constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Knawat\MPFactory $mpFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Knawat\MPFactory $mpFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $configModel
    ) {
        parent::__construct($context);
        $this->mpFactory = $mpFactory;
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->configModel = $configModel;
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

    /**
     * @return MP
     */
    public function createMP()
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
     * @param $path
     * @param $value
     * @return \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    public function setConfig($path, $value)
    {
        return $this->configInterface->saveConfig($path, $value, 'default', 0);
    }

    /**
     * Run New import Process
     *
     * @return Logger
     */
    public function getLogger()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/knawat_products.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        return $logger;
    }

    /**
     * Get Latest Config Data(Bypassing Cache)
     *
     * @param string $path Config path.
     * @return string Config value.
     */
    public function getConfigDirect($path, $partial = false)
    {
        if ($partial) {
            $path = self::PATH_KNAWAT_DEFAULT.$path;
        }
        $configConnection = $this->configModel->getConnection();
        $selectData = $configConnection->select()->from($this->configModel->getMainTable())->where('path=?', $path);
        $configData = $configConnection->fetchRow($selectData);
        if (!empty($configData) && isset($configData['value'])) {
            $configData = $configData['value'];
            return $configData;
        }
        return false;
    }
}
