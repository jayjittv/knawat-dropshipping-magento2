<?php
namespace Knawat\Dropshipping\Controller\Manage;

use \Magento\Framework\App\Action\Action;


/**
 * Class Updateproduct
 * @package Knawat\Dropshipping\Controller\Manage
 */
class Updateproduct extends Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /**
     * @var \Knawat\Dropshipping\Helper\SingleProductUpdate
     */
    protected $singleProductHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->singleProductHelper = $singleProductHelper;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $sku = $this->getRequest()->getParam('sku');
        $this->singleProductHelper->runSingleImport($sku);
    }

}