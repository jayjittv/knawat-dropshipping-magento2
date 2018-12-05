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
     * @var \Knawat\Dropshipping\Helper\productImport
     */
    protected $helper;

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\productImport $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * save and update import tab's information
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        echo "<pre>";
        print_r( $this->helper->Import());
        die();
        $productLimit = $params['product_batch_size'];
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
