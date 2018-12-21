<?php

namespace Knawat\Dropshipping\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

/**
 * Class OrderSync
 * @package Knawat\Dropshipping\Model\System\Message
 */
class OrderSync implements MessageInterface
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Knawat\Dropshipping\Helper\ManageOrders
     */
    protected $orderHelper;


    /**
     * OrderSync constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Knawat\Dropshipping\Helper\ManageOrders $orderHelper
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Knawat\Dropshipping\Helper\ManageOrders $orderHelper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'knawat_order_sync_message';

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if(count($this->orderHelper->getFailedOrders()) > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('dropshipping/dropshipping/ordersync/');
        $message = __('Some orders are not sycronized with knawat.com, Please <a href="%1">synchronize it now</a>.', $url);
        return $message;

    }

    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }


}
