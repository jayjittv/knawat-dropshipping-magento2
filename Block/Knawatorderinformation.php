<?php
namespace Knawat\Dropshipping\Block;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;

/**
 * Class Knawatorderinformation
 * @package Knawat\Dropshipping\Block
 */
class Knawatorderinformation extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }


    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
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
