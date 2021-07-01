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
     * @var \Knawat\Dropshipping\Helper\CommonHelper
     */
    protected $commonHelper;
    /**
     * @var \Knawat\Dropshipping\Helper\General
     */
    protected $generalHelper;

    const PATH_KNAWAT_DEFAULT = 'knawat/store/';


    /**
     * Saveimport constructor.
     * @param Context $context
     * @param \Knawat\Dropshipping\Helper\CommonHelper $commonHelper
     * @param \Knawat\Dropshipping\Helper\General $generalHelper
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\CommonHelper $commonHelper,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        parent::__construct($context);
        $this->commonHelper = $commonHelper;
        $this->generalHelper = $generalHelper;
    }

    /**
     * save and update import tab's information
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $operation = $this->getRequest()->getParam('operation');
        if (!empty($operation) && $operation == 'stop_import') {
            // Stop Import
            $this->commonHelper->stopImport();
            $this->messageManager->addSuccessMessage(__('Knawat Product import has been stopped successfully.'));
        } else {
            $lastImported = self::PATH_KNAWAT_DEFAULT.'knawat_last_imported';
            $this->generalHelper->setConfig($lastImported,null);
            $importProcessLock = self::PATH_KNAWAT_DEFAULT.'kdropship_import_process_lock';
            $this->generalHelper->setConfig($importProcessLock,null);

            $this->commonHelper->runImport('manual');
            $this->messageManager->addSuccessMessage(__('Knawat Product import has been started successfully.'));
        }

        // Redirect to import page.
        $this->_redirect('dropshipping/dropshipping/import/');
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
