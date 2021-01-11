<?php
namespace Knawat\Dropshipping\Block\Item;

use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer as DefaultRenderer;

class Renderer extends DefaultRenderer
{
    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    protected $_productOptionFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
        protected $request;

        /**
         * @var \Magento\Catalog\Model\ProductFactory
         */
        protected $productFactory;

        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
         */
        private $itemCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection|null
     */
    private $itemCollection;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory = null,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->request = $request;
        $this->itemCollectionFactory = $itemCollectionFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class);
        $this->productFactory = $productFactory;
        parent::__construct($context, $string, $productOptionFactory, $data);
    }

    /**
     * Get item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Check Items for Knawat
     *
     */
    public function checkIsKnawat()
    {
        if ($this->getProduct()->getIsKnawat()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Knawat Tracking Information
     *
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
    
    /**
     * Check Page is sales order view 
     *
     */
    public function getSalesController() {
        $controller = $this->request->getControllerName();
        $action     = $this->request->getActionName();
        $route      = $this->request->getRouteName();
        return $controller.$action.$route;
    }

        /**
         * Check All items if of Knawat
         *
         */
    public function getCheckKnawatItems() {
        $this->itemCollection = $this->itemCollectionFactory->create();
        $this->itemCollection->setOrderFilter($this->getOrder());
        $this->itemCollection->filterByParent(null);
        $itemDetails = $this->itemCollection->getItems();
        $itemCount = count($itemDetails);
        $i = 0;
        foreach ($itemDetails as  $value) {
            $product = $this->productFactory->create();
            $productData = $product->load($product->getIdBySku($value->getSku()));
            if ($productData->getIsKnawat()) {
                $i++;
            }
        }
        
        return ($itemCount != $i) && ($i > 0);
    }
}