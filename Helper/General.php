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

    /**
     * Time exceeded.
     *
     * Ensures the batch never exceeds a sensible time limit.
     * A timeout limit of 30s is common on shared hosting.
     *
     * @param int $startTime
     * @return bool
     */
    public function timeExceeded($startTime)
    {
        $max_time = 20; // 20 seconds.
        if (function_exists('ini_get')) {
            $max_execution_time = ini_get('max_execution_time');
            if (is_numeric($max_execution_time) && $max_execution_time > 0) {
                if ($max_execution_time >= 30) {
                    $max_execution_time -= 10;
                }
                $max_time = $max_execution_time;
            }
        }
        $time_limit = min(50, $max_time);

        $finish = $startTime + $time_limit;
        if (time() >= $finish) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Memory exceeded
     *
     * Ensures the batch process never exceeds 90%
     * of the maximum WordPress memory.
     *
     * @return bool
     */
    public function memoryExceeded()
    {
        $memory_limit   = $this->getMemoryLimit() * 0.9; // 90% of max memory
        $current_memory = memory_get_usage(true);

        if ($current_memory >= $memory_limit) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get memory limit
     *
     * @return int
     */
    public function getMemoryLimit()
    {
        if (function_exists('ini_get')) {
            $memory_limit = ini_get('memory_limit');
        } else {
            // Sensible default.
            $memory_limit = '128M';
        }

        if (! $memory_limit || -1 === intval($memory_limit)) {
            // Unlimited, set to 32GB.
            $memory_limit = '32000M';
        }

        return intval($memory_limit) * 1024 * 1024;
    }

    /**
     * Generate Random string.
     *
     * @param integer $length
     * @return string $randomString
     */
    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
