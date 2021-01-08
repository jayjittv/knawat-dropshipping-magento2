<?php
namespace Knawat\Dropshipping\Block\Order;

/**
 * Class Items
 * @package Knawat\Dropshipping\Block
 */
class Items extends \Magento\Sales\Block\Order\Items
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Order items per page.
     *
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection|null
     */
    private $itemCollection;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory|null $itemCollectionFactory
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = [],
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory = null,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->itemCollectionFactory = $itemCollectionFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class);
        parent::__construct($context, $registry, $data, $itemCollectionFactory);
    }

    /**
     * Check Items for Knawat
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
    
    /**
     * Check Page is sales order view 
     *
     */
    public function getSalesController() {
        $moduleName = $this->request->getModuleName();
        $controller = $this->request->getControllerName();
        $action     = $this->request->getActionName();
        $route      = $this->request->getRouteName();
        return $controller.$action.$route;
    }

}
