<?php
namespace Knawat\Dropshipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class ConfigObserver implements ObserverInterface
{

    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $confighelper;
    
    public function __construct(
        \Knawat\Dropshipping\Helper\ManageConfig $confighelper
    ) {
        $this->confighelper = $confighelper;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->confighelper->isKnawatEnabled()) {
            $mp = $this->confighelper->createMP();
            if (!empty($mp)) {
                $token= $mp->getAccessToken();
                if ($token == '') {
                    $this->confighelper->addStoreWarning();
                }
            }
        }
    }
}
