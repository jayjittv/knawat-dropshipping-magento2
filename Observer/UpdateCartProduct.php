<?php
namespace Knawat\Dropshipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class UpdateCartProduct
 * @package Knawat\Dropshipping\Observer
 */
class UpdateCartProduct implements ObserverInterface
{

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @var \Knawat\Dropshipping\Helper\SingleProductUpdate
     */
    protected $singleProductHelper;

    /**
     * UpdateCartProduct constructor.
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper
     */
    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper
    ) {
        $this->productModel = $productModel;
        $this->singleProductHelper = $singleProductHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getQuoteItem();
        $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
        $id = $item->getProduct()->getId();
        $product = $this->productModel->load($id);
        $isKnawat = $product->getIsKnawat();
        if ($isKnawat) {
            $sku = $product->getSku();
            if (!empty($sku)) {
                $this->singleProductHelper->productUpdate($sku);
            }
        }
    }
}
