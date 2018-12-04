<?php
namespace Knawat\Dropshipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddressSave
 * @package Knawat\Dropshipping\Observer
 */
class AddressSave implements ObserverInterface
{

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $orderhelper;

    /**
     * AddressSave constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Knawat\Dropshipping\Helper\ManageOrders $orderhelper
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Knawat\Dropshipping\Helper\ManageOrders $orderhelper
    ) {
        $this->request = $request;
        $this->orderhelper = $orderhelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $action     = $this->request->getActionName();
        $address = $observer->getEvent()->getAddress();
        $order = $address->getOrder();
        if ($action == 'addressSave' && $action != '') {
            $isKnawat = $this->orderhelper->getIsKnawat($order->getId());
            if ($isKnawat == 1) {
                $this->orderhelper->knawatOrderCreatedUpdated($order->getId());
            }
        }
    }
}
