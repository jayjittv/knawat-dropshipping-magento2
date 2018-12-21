<?php
namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;

/**
 * Class Ordersync
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Ordersync extends \Magento\Backend\App\Action
{
    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $orderHelper;

    /**
     * Ordersync constructor.
     * @param Context $context
     * @param \Knawat\Dropshipping\Helper\ManageOrders $orderHelper
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\ManageOrders $orderHelper
    ) {
        parent::__construct($context);
        $this->orderHelper = $orderHelper;
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {

        $orderData = $this->orderHelper->getFailedOrders();
        foreach ($orderData as $orderId) {
            $this->orderHelper->knawatOrderCreatedUpdated($orderId);
        }
        if (count($this->orderHelper->getFailedOrders()) > 0) {
            $this->messageManager->addErrorMessage(__('Something went wrong during order synchronization, please try again.'));
        } else {
            $this->messageManager->addSuccessMessage(__('Order(s) has been synchronized successfully.'));
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
        return $this->_authorization->isAllowed('Knawat_Dropshipping::ordersync');
    }
}
