<?php

namespace Knawat\Dropshipping\Helper;

use Knawat\Dropshipping\MP;

/**
 * Class ManageConfig
 * @package Knawat\Dropshipping\Helper
 */
class ManageConfig extends \Magento\Framework\App\Helper\AbstractHelper
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Knawat\Dropshipping\MPFactory
     */
    protected $mpFactory;

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
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        parent::__construct($context);
        $this->mpFactory = $mpFactory;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get config data from DB
     *
     * @param string $path
     * @return string
     */
    protected function getConfigData($path)
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
        if (($consumer_key != '') && ($consumer_secret != '')) {
            if ($this->mpApi == null) {
                $mp = $this->mpFactory->create([
                    'consumer_key' => $consumer_key,
                    'consumer_secret' => $consumer_secret,
                ]);

                return $this->mpApi = $mp;
            } else {
                return $this->mpApi;
            }
        } else {
            return false;
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
     * @return bool
     */
    public function checkKeyNotAvailable()
    {
        $consumer_key = $this->getConfigData('consumer_key');
        $consumer_secret = $this->getConfigData('consumer_secret');
        if (($consumer_key == '') || ($consumer_secret = '')) {
            return true;
        }
    }

    public function getToken() {
        $mp = $this->createMP();
        if (!empty($mp)) {
                $token = $mp->getAccessToken();
                if ($token != '') {
                    return $token;
                } else {
                    return false;
                }
            }
            return false;

    }
}
