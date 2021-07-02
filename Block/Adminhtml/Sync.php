<?php

namespace Knawat\Dropshipping\Block\Adminhtml;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Sync
 * @package Knawat\Dropshipping\Block\Adminhtml
 */
class Sync extends \Magento\Backend\Block\Template
{

    /**
     * @var \Knawat\Dropshipping\Helper\General
     */
    protected $generalHelper;

    /**
     * @var \Knawat\Dropshipping\Helper\ManageConfig
     */
    protected $configHelper;

    /**
     * @var \Knawat\Dropshipping\Helper\CommonHelper
     */
    protected $commonHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $date;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    
    const PATH_KNAWAT_DEFAULT = 'knawat/store/';


    /**
     * Sync constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Knawat\Dropshipping\Helper\General $generalHelper
     * @param \Knawat\Dropshipping\Helper\ManageConfig $configHelper
     * @param \Knawat\Dropshipping\Helper\CommonHelper $commonHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Knawat\Dropshipping\Helper\General $generalHelper,
        \Knawat\Dropshipping\Helper\ManageConfig $configHelper,
        \Knawat\Dropshipping\Helper\CommonHelper $commonHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->generalHelper = $generalHelper;
        $this->configHelper = $configHelper;
        $this->commonHelper = $commonHelper;
        $this->date = $date;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImportCount()
    {
        $productCount = $this->generalHelper->getConfigDirect('knawat_last_imported_count', true);
        if (!empty($productCount)) {
            return $productCount;
        }
        return false;
    }

    /**
     * @return bool|string
     */
    public function getLastImportTime()
    {
        $lastImportTime = $this->generalHelper->getConfigDirect('knawat_last_imported', true);
        if (!empty($lastImportTime)) {
            return $lastImportTime;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getKnawatConnection()
    {
        if ($this->configHelper->getToken()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $path
     * @return bool
     */
    public function getTotalProductsbyPath($path)
    {
        $mp = $this->commonHelper->createMP();
        if (!empty($mp)) {
            $token = $mp->getAccessToken();
            if ($token != '') {
                if ($mp->client->get($path)->total) {
                    return $mp->client->get($path)->total;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getTotalInStockProducts()
    {
        $path = '/catalog/products/count?hideOutOfStock=1';
        if ($this->getTotalProductsbyPath($path)) {
            return $this->getTotalProductsbyPath($path);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getTotalProducts()
    {
        $path = '/catalog/products/count';
        if ($this->getTotalProductsbyPath($path)) {
            return $this->getTotalProductsbyPath($path);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getLastSyncProducts()
    {

        if ($this->getLastImportTime()) {
            $path = '/catalog/products/count?lastUpdate=' . $this->getLastImportTime();
            if ($this->getTotalProductsbyPath($path)) {
                $syncProducts = $this->getTotalProducts() - $this->getTotalProductsbyPath($path);

                if ($syncProducts) {
                    return $syncProducts;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * @return bool|\Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTimeMinutes()
    {
        $lastImportProcessTime = $this->generalHelper->getConfigDirect('knawat_last_imported_process_time', true);
        $date1 = $lastImportProcessTime;
        $date2 = $this->date->date()->format('Y-m-d H:i:s');
        $diff = abs(strtotime($date2) - strtotime($date1));
        $timeData = array();
        if ($diff) {
            $years = floor($diff / (365 * 60 * 60 * 24));
            $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
            $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
            $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));

            $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);

            $fullDays = floor($diff / (60 * 60 * 24));
            $importCount = $this->getImportCount();
            if ($fullDays) {
                return $updatedString = __("We just updated <b>" . $importCount . " products " . $fullDays . " day(s) ago.</b>");
            } elseif ($hours) {
                return $updatedString = __("We just updated <b>" . $importCount . " products " . $hours . " hour(s) ago.</b>");
            } elseif ($minuts) {
                return $updatedString = __("We just updated <b>" . $importCount . " products " . $minuts . " minut(s) ago.</b>");
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @return bool|false|float
     */
    public function getSyncBarAmount()
    {
        $totalProducts = $this->getTotalProducts();
        $syncProducts = $this->getLastSyncProducts();
        if ($totalProducts && $syncProducts) {
            $syncBarAmount = ($syncProducts * 100) / $totalProducts;
            return round($syncBarAmount);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getSyncAllUrl()
    {
        $url = $this->urlBuilder->getUrl('dropshipping/dropshipping/productsyncbar/');
        return $url;
    }
}
