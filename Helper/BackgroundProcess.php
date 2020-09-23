<?php

namespace Knawat\Dropshipping\Helper;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class BackgroundProcess
 *
 * @package Knawat\Dropshipping\Helper
 */
class BackgroundProcess extends \Magento\Framework\App\Helper\AbstractHelper
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
     * @var string
     */
    protected $action = 'background_process';

    /**
     * Identifier
     *
     * @var mixed
     * @access protected
     */
    protected $identifier;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $configInterface;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var
     */
    protected $start_time;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configModel;

    /**
     * @var General
     */
    protected $generalHelper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ManageConfig constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Knawat\MPFactory $mpFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Knawat\Dropshipping\Helper\General $generalHelper,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->configInterface = $configInterface;
        $this->curl = $curl;
        $this->cache = $cache;
        $this->configModel = $configModel;
        $this->generalHelper = $generalHelper;

        $this->identifier = self::PATH_KNAWAT_DEFAULT.$this->action;
        $this->serializer = $serializer;
    }

    /**
     * Get Identifier Key.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
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

    /**
     * Save config value to the storage resource
     *
     * @param string $path
     * @param string $value
     * @return \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    public function setConfig($path, $value)
    {
        return $this->configInterface->saveConfig($path, $value, 'default', 0);
    }

    /**
     * Delete config value from the storage resource
     *
     * @param string $path
     * @return \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    public function deleteConfig($path)
    {
        return $this->configInterface->deleteConfig($path, 'default', 0);
    }

    /**
     * Dispatch Async request.
     *
     * @access public
     * @return void
     */
    public function dispatch($start = false)
    {
        $knawatKey = $this->generalHelper->getConfigDirect('knawt_security', true);
        if (empty($knawatKey)) {
            $knawatKey = $this->generalHelper->generateRandomString();
            $this->setConfig(self::PATH_KNAWAT_DEFAULT."knawt_security", $knawatKey);
        }
        $encryptedKey = hash('sha256', $knawatKey);
        if ($start) {
            $this->setConfig($this->identifier.'_start_time', time());
        }
        try {
            $this->curl->setTimeout(1);
            $url = $this->storeManager->getStore()->getBaseUrl()."dropshipping/manage/request?knawat_key=$encryptedKey";
            $this->curl->get($url);
        } catch (\Exception $e) {
            return true;
        }
        return true;
    }

    /**
     * Maybe process queue
     *
     * Checks whether data exists within the queue and that
     * the process is not already running.
     */
    public function maybeHandle()
    {
        // Don't lock up other requests while processing
        session_write_close();

        if ($this->isProcessRunning()) {
            // Background process already running.
            return false;
        }

        if ($this->isQueueEmpty()) {
            return false;
        }

        // @todo  Security Validation.
        // check_ajax_referer( $this->identifier, 'nonce' );
        $this->handle();
        return false;
    }

    /**
     * Handle cron healthcheck
     *
     * Restart the background process if not already running
     * and data exists in the queue.
     */
    public function cronHealthCheck()
    {
        if ($this->isProcessRunning()) {
            // Background process already running.
            return false;
        }

        if ($this->isQueueEmpty()) {
            return false;
        }
        $this->handle();
        return false;
    }

    /**
     * Push to queue
     *
     * @param mixed $data Data.
     *
     * @return $this
     */
    public function pushToQueue($data)
    {
        if (!empty($data)) {
            $this->setConfig($this->identifier, $this->serializer->serialize($data));
        }
        return $this;
    }

    /**
     * Handle
     *
     * Pass each queue item to the task handler, while remaining
     * within server memory and time limit constraints.
     */
    public function handle()
    {
        // Lock Process
        $this->lockProcess();

        do {
            $batch = $this->getBatch();

            $task = $this->task($batch);

            if (false !== $task) {
                $batch = $task;
            } else {
                $batch = false;
            }

            // Update or delete current batch.
            if (! empty($batch)) {
                $this->setConfig($this->identifier, $this->serializer->serialize($batch));
            } else {
                $this->deleteConfig($this->identifier);
            }
        } while (! $this->generalHelper->timeExceeded($this->start_time) && ! $this->generalHelper->memoryExceeded() && ! $this->isQueueEmpty());

        // UnLockProcess
        $this->unlockProcess();

        // Start next batch or complete process.
        if (! $this->isQueueEmpty()) {
            $this->dispatch();
        }
        return true;
    }

    /**
     * Get batch
     *
     * @return Array Return the first batch from the queue
     */
    public function getBatch()
    {
        $importData = false;
        $configConnection = $this->configModel->getConnection();
        $select = $configConnection->select()->from($this->configModel->getMainTable())->where('path=?', $this->identifier);
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

    /**
     * Lock process
     *
     * Lock the process so that multiple instances can't run simultaneously.
     * Override if applicable, but the duration should be greater than that
     * defined in the time_exceeded() method.
     */
    public function lockProcess()
    {
        $this->start_time = time(); // Set start time of current process.

        $lock_duration = 120;
        $this->setConfig($this->identifier.'_process_lock', time() + $lock_duration);
        // $this->cache->save($this->start_time, 'process_lock', ['lock_process'], $lock_duration);
    }

    /**
     * Unlock process
     *
     * Unlock the process so that other instances can spawn.
     *
     * @return $this
     */
    public function unlockProcess()
    {
        $this->deleteConfig($this->identifier.'_process_lock');
        // $this->cache->clean('lock_process');
        return $this;
    }

    /**
     * Is process running
     *
     * Check whether the current process is already running
     * in a background process.
     */
    public function isProcessRunning()
    {
        $importData = false;
        $configConnection = $this->configModel->getConnection();
        $select = $configConnection->select()->from($this->configModel->getMainTable())->where('path=?', $this->identifier.'_process_lock');
        $lockData = $configConnection->fetchRow($select);
        if (!empty($lockData) && isset($lockData['value'])) {
            $lockData = $lockData['value'];
            if (time() >= $lockData) {
                return false;
            }
            return true;
        }
        return false;
        /* if ($this->cache->load('process_lock')) {
            // Process already running.
            return true;
        }
        return false; */
    }

    /**
     * Is queue empty
     *
     * @return bool
     */
    public function isQueueEmpty()
    {
        $importConfig = $this->getBatch();
        if (!empty($importConfig)) {
            return false;
        }
        return true;
    }

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over.
     *
     * @return mixed
     */
    protected function task($item)
    {
        // Implement in Child Class
        return false;
    }

    /**
     * Kill process.
     *
     * Stop processing queue items, clear cronjob and delete all batches.
     */
    public function killProcess()
    {
        if (! $this->isQueueEmpty()) {
            // Delete Database Batch
            $this->deleteConfig($this->identifier);

            // For stop process at processing level
            $stopInterval = 120;
            $this->setConfig($this->identifier.'_stop_import', time() + $stopInterval);
        }
    }
}
