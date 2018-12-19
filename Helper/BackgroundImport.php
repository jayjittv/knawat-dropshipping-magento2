<?php
namespace Knawat\Dropshipping\Helper;

/**
 * Class BackgroundImport
 *
 * @package Knawat\Dropshipping\Helper
 */
class BackgroundImport extends \Knawat\Dropshipping\Helper\BackgroundProcess
{
    /**
     * @var string
     */
    protected $action = 'kdropship_import';

    /**
     * @var \Knawat\Dropshipping\Helper\ProductImport
     */
    protected $importer;

    /**
     * @var \Knawat\Dropshipping\Helper\General
     */
    protected $generalHelper;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * BackgroundImport constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Knawat\Dropshipping\Helper\ProductImport $importer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Knawat\Dropshipping\Helper\ProductImport $importer,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $storeManager,
            $configInterface,
            $curl,
            $cache,
            $configModel,
            $generalHelper
        );
        $this->importer = $importer;
        $this->generalHelper = $generalHelper;
        $this->cache = $cache;
    }

    /**
     * Task
     *
     * Perform Product Import Here.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($item = [])
    {
        $logger = $this->generalHelper->getLogger();
        $logger->info("Item: " . print_r($item, true));

        $results = $this->importer->import('full', $item);
        $logger->info("Results: " . print_r($results, true));

        $params = $this->importer->getImportParams();
        if (isset($results['status']) && 'fail' === $results['status']) {
            return false;
        }

        $identifier = $this->getIdentifier();
        $stopImportPath = $identifier.'_stop_import';
        $stopImport = $this->generalHelper->getConfigDirect($stopImportPath);
        if ($stopImport) {
            if ($stopImport > time()) {
                $params['is_complete'] = true;
                $params['force_stopped'] = true;
            }
            $this->deleteConfig($stopImportPath);
        }

        if ($params['is_complete']) {
            // Send success.
            $item = $params;
            $item['imported'] += count($results['imported']);
            $item['failed']   += count($results['failed']);
            $item['updated']  += count($results['updated']);
            $item['skipped']  += count($results['skipped']);

            if (!isset($params['force_stopped'])) {
                // update option on import finish.
                $startTime = $this->generalHelper->getConfigDirect($identifier.'_start_time');
                if (empty($startTime)) {
                    $startTime = time();
                }
                $lastImportPath = parent::PATH_KNAWAT_DEFAULT.'knawat_last_imported';
                $this->setConfig($lastImportPath, $startTime);
            }

            // Logs import data
            $logger->info("[IMPORT_STATS_FINAL]" . print_r($item, true));

            // Return false to complete background import.
            return false;
        } else {
            $item = $params;
            if ($params['products_total'] == ( $params['product_index'] + 1 )) {
                $item['page']  = $params['page'] + 1;
                $item['product_index']  = -1;
            } else {
                $item['page']  = $params['page'];
                $item['product_index']  = $params['product_index'];
            }
            $item['imported'] += count($results['imported']);
            $item['failed']   += count($results['failed']);
            $item['updated']  += count($results['updated']);
            $item['skipped']  += count($results['skipped']);
            // Return Update Item to importer
            return $item;
        }
        // Return false to complete background import.
        return false;
    }
}
