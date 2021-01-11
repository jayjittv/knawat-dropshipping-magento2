<?php
namespace Knawat\Dropshipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderSave
 * @package Knawat\Dropshipping\Observer
 */
class OrderSave implements ObserverInterface
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
     * OrderSave constructor.
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
        $route = $this->request->getRouteName();
        $order = $observer->getEvent()->getOrder();
        if ($route == 'sales' && $route != '') {
            $isKnawat = $this->orderhelper->getIsKnawat($order->getId());
            if ($isKnawat == 1) {
                $this->orderhelper->knawatOrderCreatedUpdated($order->getId());
            }
        }
    }
}
