<?php
namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;

/**
 * Class Productsyncbar
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Productsyncbar extends \Magento\Backend\App\Action
{
    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $orderHelper;
    /**
     * @var generalHelper
     */
    protected $generalHelper;

    const PATH_KNAWAT_DEFAULT = 'knawat/store/';

    /**
     * Productsyncbar constructor.
     * @param Context $context
     * @param \Knawat\Dropshipping\Helper\ManageOrders $orderHelper
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $importStartTime = self::PATH_KNAWAT_DEFAULT.'kdropship_import_start_time';
        $lastImportCount = self::PATH_KNAWAT_DEFAULT.'knawat_last_imported_count';
        $lastImported = self::PATH_KNAWAT_DEFAULT.'knawat_last_imported';
        $lastImportedProcessTime = self::PATH_KNAWAT_DEFAULT.'knawat_last_imported_process_time';
        $importProcessLock = self::PATH_KNAWAT_DEFAULT.'kdropship_import_process_lock';
        $configArray = array($importStartTime,$lastImportCount,$lastImported,$lastImportedProcessTime,$importProcessLock);
        foreach ($configArray as $configValue) {
            $this->generalHelper->setConfig($configValue,null);    
        }
        $this->_redirect($this->_redirect->getRefererUrl());
    }



    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knawat_Dropshipping::productsyncbar');
    }
}
