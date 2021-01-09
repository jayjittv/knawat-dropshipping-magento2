<?php

namespace Knawat\Dropshipping\Helper;

use Knawat\Dropshipping\MP;

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
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * ManageConfig constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Knawat\MPFactory $mpFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Knawat\Dropshipping\MPFactory $mpFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository
    ) {
        parent::__construct($context);
        $this->mpFactory = $mpFactory;
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->configModel = $configModel;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Get config data from DB
     *
     * @param string $path
     * @param string $store
     * @param integer $scopeId
     * @return string
     */
    public function getConfigData($path, $store = 'default', $scopeId = 0)
    {
        return $this->scopeConfig->getValue(self::PATH_KNAWAT_DEFAULT.$path, $store, $scopeId);
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
     * Get Websites with store views and languages
     *
     * @param  boolean $withAdmin
     * @return array websites
     */
    public function getWebsites($withAdmin = true)
    {
        $stores = $this->storeRepository->getList();
        $websites = [];
        $enabaledWebsites = [];
        $defaultConsumerKey = $this->getConfigData('consumer_key');
        $defaultConsumerSecret = $this->getConfigData('consumer_secret');
        foreach ($stores as $store) {
            if ($store["code"] == 'admin' && !$withAdmin) {
                continue;
            }
            $websiteId = $store["website_id"];
            if (in_array($websiteId, $enabaledWebsites)) {
                continue;
            }

            if (!empty($defaultConsumerKey) && !empty($defaultConsumerSecret)) {
                $enabaledWebsites[] = $websiteId;
            } else {
                $websiteConsumerKey = $this->getConfigData('consumer_key', "websites", $websiteId);
                $websiteConsumerSecret = $this->getConfigData('consumer_secret', "websites", $websiteId);
                if (!empty($websiteConsumerKey) && !empty($websiteConsumerSecret)) {
                        $enabaledWebsites[] = $websiteId;
                }
            }
        }
        foreach ($stores as $store) {
            if ($store["code"] == 'admin' && !$withAdmin) {
                continue;
            }
            $websiteId = $store["website_id"];
            if (!in_array($websiteId, $enabaledWebsites)) {
                continue;
            }
            $storeId = $store["store_id"];
            $storeData = [
                'code' => $store["code"]
            ];
            $defaultConfigLanguage = $this->getConfigData('store_language');
            $storeData['lang'] = $defaultConfigLanguage;
            $storeConfigLanguage = $this->getConfigData('store_language', "stores", $storeId);
            if (!empty($storeConfigLanguage)) {
                $storeData['lang'] = $storeConfigLanguage;
            }
            if (empty($storeConfigLanguage)) {
                $websiteConfigLanguage = $this->getConfigData('store_language', "websites", $websiteId);
                if (!empty($websiteConfigLanguage)) {
                    $storeData['lang'] = $websiteConfigLanguage;
                }
            }
            $websites[$websiteId][$storeId] = $storeData;
        }
        return $websites;
    }

    /**
     * Get Websites with store views and languages
     *
     * @param  boolean $withAdmin
     * @return array websites
     */
    public function getDefaultLanguage()
    {
        $defaultConfigLanguage = $this->getConfigData('store_language');
        if (empty($defaultConfigLanguage)) {
            $defaultConfigLanguage = 'en';
            $stores = $this->storeRepository->getList();
            foreach ($stores as $store) {
                if ($store["code"] == 'admin') {
                    continue;
                }
                $websiteConsumerKey = $this->getConfigData('consumer_key', "websites", $websiteId);
                $websiteConsumerSecret = $this->getConfigData('consumer_secret', "websites", $websiteId);
                $websiteConfigLanguage = $this->getConfigData('store_language', "websites", $websiteId);
                if (!empty($websiteConsumerKey) && !empty($websiteConsumerSecret) && !empty($websiteConfigLanguage)) {
                    $defaultConfigLanguage = $websiteConfigLanguage;
                    break;
                }
            }
        }
        return $defaultConfigLanguage;
    }

    /**
     * Get Weight Multiplier for convert it to as per magento weight unit.
     */
    public function getWeightMultiplier()
    {
        $weightUnit = $this->scopeConfig->getValue('general/locale/weight_unit');
        if (empty($weightUnit)) {
            $weightUnit = 'lbs';
        }

        if ($weightUnit === 'kgs') {
            return 1;
        }
        return 2.20462;
    }

    /**
     * Generate Random string.
     *
     * @param integer $length
     * @return string $randomString
     */
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
