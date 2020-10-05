<?php

namespace Knawat\Dropshipping\Helper;

/**
 * Class SingleProductUpdate
 * @package Knawat\Dropshipping\Helper
 */
class SingleProductUpdate extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Knawat\Dropshipping\Helper\ProductImport
     */
    protected $importer;

    /**
     * @var General
     */
    protected $generalHelper;

    /**
     *knawat config constant
     */
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';

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
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Knawat\Dropshipping\Helper\ProductImport $importer,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->storeManager = $storeManager;
        $this->importer = $importer;
        $this->generalHelper = $generalHelper;
    }

    /**
     * @param $sku
     * @return bool
     */
    public function productUpdate($sku)
    {
        $knawatKey = $this->generalHelper->getConfigDirect('knawt_security', true);
        if (empty($knawatKey)) {
            $knawatKey = $this->generalHelper->generateRandomString();
            $this->generalHelper->setConfig(self::PATH_KNAWAT_DEFAULT."knawt_security", $knawatKey);
        }
        $encryptedKey = hash('sha256', $knawatKey);
        try {
            $this->curl->setTimeout(1);
            $url = $this->storeManager->getStore()->getBaseUrl()."dropshipping/manage/updateproduct?knawat_key=$encryptedKey&sku=$sku";
            $this->curl->get($url);
        } catch (\Exception $e) {
            return true;
        }
        return true;
    }

    /**
     * @param $sku
     */
    public function runSingleImport($sku)
    {
        $item = ['sku' => $sku];
        $this->importer->import('single', $item);
    }
}
