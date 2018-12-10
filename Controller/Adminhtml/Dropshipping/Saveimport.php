<?php

namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Saveimport
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Saveimport extends \Magento\Backend\App\Action
{
    /**
     * @var \Knawat\Dropshipping\Helper\ProductImport
     */
    protected $importer;

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\ProductImport $importer
    ) {
        parent::__construct($context);
        $this->importer = $importer;
    }

    /**
     * save and update import tab's information
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $results = $this->runImport('full', array( 'limit' => 10, 'page' => 1 ) );
        echo "<pre>";
        print_r( $results );
        if( !empty( $results ) && $results != false ){
            $results = $this->runImport('full', $results );
        }
        print_r( $results );
        $productLimit = $params['product_batch_size'];
    }


    protected function runImport( $importType = 'full', $item = array() ){
        $results = $this->importer->import( $importType, $item );
        print_r( $results );
        $params = $this->importer->getImportParams();
        if( isset( $results['status'] ) && 'fail' === $results['status'] ){
			return false;
        }
        
        if ( $params['is_complete'] ) {

			// Send success.
			$item = $params;

			$item['imported'] += count( $results['imported'] );
			$item['failed']   += count( $results['failed'] );
            $item['updated']  += count( $results['updated'] );
            $item['skipped']  += count( $results['skipped'] );

			// // update option on import finish.
			// update_option( 'knawat_full_import', 'done', false );
			// update_option( 'knawat_last_imported', time(), false );
			// // Logs import data
			// knawat_dropshipwc_logger( '[IMPORT_STATS_FINAL]'.print_r( $item, true ), 'info' );
			// knawat_dropshipwc_logger( '[FAILED_IMPORTS]'.print_r( $error_log, true ) );

			// Return false to complete background import.
			return false;

		} else {

			$item = $params;
			if( $params['products_total'] == ( $params['product_index'] + 1 ) ){
				$item['page']  = $params['page'] + 1;
				$item['product_index']  = -1;
			}else{
				$item['page']  = $params['page'];
				$item['product_index']  = $params['product_index'];
			}

			$item['imported'] += count( $results['imported'] );
			$item['failed']   += count( $results['failed'] );
            $item['updated']  += count( $results['updated'] );
            $item['skipped']  += count( $results['skipped'] );
			// Return Update Item to importer
			return $item;
		}
		// Return false to complete background import.
		return false;
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knawat_Dropshipping::saveimport');
    }
}
