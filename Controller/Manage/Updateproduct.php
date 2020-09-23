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
     * @var \Knawat\Dropshipping\Helper\General
     */
    protected $generalHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->singleProductHelper = $singleProductHelper;
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $knawatParams = $this->getRequest()->getParam("knawat_key");
        $knawatKey = $this->generalHelper->getConfigDirect('knawt_security', true);
        if (hash('sha256', $knawatKey) === $knawatParams) {
            $sku = $this->getRequest()->getParam('sku');
            $this->singleProductHelper->runSingleImport($sku);
        }
    }
}
