<?php
namespace Knawat\Dropshipping\Controller\Manage;

use \Magento\Framework\App\Action\Action;

/**
 * Class Request
 * @package Knawat\Dropshipping\Controller\Manage
 */
class Request extends Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /**
     * @var \Knawat\Dropshipping\Helper\BackgroundImport
     */
    protected $backgroundImport;

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
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundImport,
        \Knawat\Dropshipping\Helper\General $generalHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->backgroundImport = $backgroundImport;
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $knawatParams = $this->getRequest()->getParam("knawat_key");
        $knawatKey = $this->generalHelper->getConfigDirect('knawt_security',true);
        if (md5($knawatKey) === $knawatParams) {
            $this->backgroundImport->maybeHandle();
        }
    }
}
