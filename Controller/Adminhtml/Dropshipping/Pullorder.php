<?php

namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;

/**
 * Class Pullorder
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Pullorder extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $orderhelper;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Knawat\Dropshipping\Helper\ManageOrders $orderhelper
    ) {
        parent::__construct($context);
        $this->moduleManager = $moduleManager;
        $this->orderhelper = $orderhelper;
    }
    /**
     * Knawat settings controller page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        if ($this->isKnawatEnabled()) {
            $pull_results = $this->orderhelper->knawatPullOrders();
            if (!empty($pull_results)) {
                while (isset($pull_results['is_complete']) && $pull_results['is_complete'] != true) {
                    $pull_results = $this->orderhelper->knawatPullOrders($pull_results);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isKnawatEnabled()
    {
        return $this->moduleManager->isEnabled('Knawat_Dropshipping');
    }
}
