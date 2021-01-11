<?php

namespace Knawat\Dropshipping\Helper;

use Knawat\Dropshipping\MP;

/**
 * Class ManageConfig
 * @package Knawat\Dropshipping\Helper
 */
class CommonHelper extends \Magento\Framework\App\Helper\AbstractHelper
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
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Knawat\Dropshipping\MPFactory
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
     * @var BackgroundImport
     */
    protected $backgroundHelper;

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
        \Knawat\Dropshipping\MPFactory $mpFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
    ) {
        parent::__construct($context);
        $this->mpFactory = $mpFactory;
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->configInterface = $configInterface;
        $this->configModel = $configModel;
        $this->backgroundHelper = $backgroundHelper;
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
     * @return bool
     */
    public function isKnawatEnabled()
    {
        return $this->moduleManager->isEnabled('Knawat_Dropshipping');
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
     * @return bool
     */
    public function runImport($is_manual = '')
    {
        $importStatus = $this->backgroundHelper->isQueueEmpty();
        if (!$importStatus) {
            return false;
        }

        if ($importStatus) {
            //add data and push to queue with dispatch
            try {
                $product_batch_size = $this->getConfigData('product_batch_size');
                if (empty($product_batch_size) || $product_batch_size < 0 || $product_batch_size > 100) {
                    $product_batch_size = 25;
                }
                $data = [];
                $data['limit'] = $product_batch_size;
                if (!empty($is_manual) && $is_manual == 'manual') {
                    $data['is_manual'] = 'true';
                }
                
                $this->backgroundHelper->pushToQueue($data)->dispatch(true);
            } catch (\Exception $e) {
                // ignore.
            }
        }
        return false;
    }

    /**
     * Stop the Current Running Import
     *
     * @return bool
     */
    public function stopImport()
    {
        try {
            $this->backgroundHelper->killProcess();
        } catch (\Exception $e) {
            // ignore.
        }
        return true;
    }

    /**
     * Run New import Process
     *
     * @return Logger
     */
    public function getLogger()
    {
        $writer = new \Zend\Log\Writer\Stream(BP.'/var/log/knawat_products.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        return $logger;
    }
}
