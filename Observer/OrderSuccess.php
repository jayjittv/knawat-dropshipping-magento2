<?php
namespace Knawat\Dropshipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderSuccess
 * @package Knawat\Dropshipping\Observer
 */
class OrderSuccess implements ObserverInterface
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
     * OrderSuccess constructor.
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
        $event = $observer->getEvent();
        $orderIds = $event->getOrderIds();
        $order_id = $orderIds[0];
        if (isset($order_id)) {
            $isKnawat = $this->orderhelper->setIsKnawat($order_id);
            if ($isKnawat == 1) {
                $this->orderhelper->knawatOrderCreatedUpdated($order_id);
            }
        }
    }
}
