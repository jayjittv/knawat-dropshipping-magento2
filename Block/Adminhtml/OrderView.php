<?php

namespace Knawat\Dropshipping\Block\Adminhtml;

/**
 * Class OrderView
 * @package Knawat\Dropshipping\Block\Adminhtml
 */
class OrderView extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $coreRegistry = null;

    /**
     * OrderView constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @param $providerName
     * @param $trackingNumber
     * @return mixed|string
     */
    public function getTracking($providerName, $trackingNumber)
    {
        if ($providerName == '' || $trackingNumber == '') {
            return '';
        }
        $trackingLink = '';
        $providersLink = [
            'dhl' => 'http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB={TRACKINGNUMBER}',
            'aramex' => 'https://www.aramex.com/track/results?ShipmentNumber={TRACKINGNUMBER}',
        ];
        $providerName = trim(strtolower($providerName));
        if ((!empty($providerName)) && array_key_exists($providerName, $providersLink)) {
            $trackingLink = str_replace('{TRACKINGNUMBER}', $trackingNumber, $providersLink[$providerName]);
        } else {
            $trackingLink = 'https://track24.net/?code='.$trackingNumber;
        }

        return $trackingLink;
    }
}
