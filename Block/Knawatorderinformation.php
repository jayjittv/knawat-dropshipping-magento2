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

    protected $productFactory;

    private $itemCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection|null
     */
    private $itemCollection;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory = null,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->coreRegistry = $registry;
        $this->itemCollectionFactory = $itemCollectionFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class);
        $this->productFactory = $productFactory;
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

    public function getCheckKnawatItems(){
        $this->itemCollection = $this->itemCollectionFactory->create();
        $this->itemCollection->setOrderFilter($this->getOrder());
        $this->itemCollection->filterByParent(null);
        $itemDetails = $this->itemCollection->getItems();
        $itemCount = count($itemDetails);
        $i = 0;
        foreach ($itemDetails as  $value) {
                $product = $this->productFactory->create();
                $productData = $product->load($product->getIdBySku($value->getSku()));
                if($productData->getIsKnawat()){
                     $i++;
                }
        }
        if($itemCount == $i){
           return true;
        }else{
            return false;
        }
    }

}
