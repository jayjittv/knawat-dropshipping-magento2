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
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Config\Model\ResourceModel\Config $configModel,
        \Knawat\Dropshipping\Helper\ProductImport $importer
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $storeManager,
            $configInterface,
            $curl,
            $formKey,
            $cache,
            $configModel
        );
        $this->importer = $importer;
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

        $logger = new \Zend\Log\Logger();
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/logforcurl.log');
        $logger->addWriter($writer);
        $logger->info("Item: " . print_r($item, true));

        $results = $this->importer->import('full', $item);
        $logger->info("Results: " . print_r($results, true));

        $params = $this->importer->getImportParams();
        if (isset($results['status']) && 'fail' === $results['status']) {
            return false;
        }

        if ($params['is_complete']) {
            // Send success.
            $item = $params;
            $item['imported'] += count($results['imported']);
            $item['failed']   += count($results['failed']);
            $item['updated']  += count($results['updated']);
            $item['skipped']  += count($results['skipped']);

            // update option on import finish.
            // update_option( 'knawat_full_import', 'done', false );
            // update_option( 'knawat_last_imported', time(), false );

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
