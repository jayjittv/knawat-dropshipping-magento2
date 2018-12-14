<?php

namespace Knawat\Dropshipping\Helper;

/**
 * Class ManageConfig
 * @package Knawat\Dropshipping\Helper
 */
class SingleProductUpdate extends \Magento\Framework\App\Helper\AbstractHelper
{

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
        \Knawat\Dropshipping\Helper\ProductImport $importer
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->storeManager = $storeManager;
        $this->importer = $importer;
    }

    public function productUpdate($sku)
    {
        $requestData = ['sku' => $sku];
        try {
            $this->curl->setTimeout(1);
            $url = $this->storeManager->getStore()->getBaseUrl()."dropshipping/manage/updateproduct/";
            $this->curl->post($url, $requestData);
        } catch (\Exception $e) {
            return true;
        }
        return true;
    }

    public function runSingleImport($sku){
      $item = array('sku' => $sku);
      $this->importer->import('single', $item);
  }


}
