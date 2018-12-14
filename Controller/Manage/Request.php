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
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundImport
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->backgroundImport = $backgroundImport;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->backgroundImport->maybeHandle();
    }
}
