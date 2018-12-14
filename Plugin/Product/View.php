<?php
namespace Knawat\Dropshipping\Plugin\Product;

/**
 * Class View
 * @package Knawat\Dropshipping\Plugin\Product
 */
class View
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Knawat\Dropshipping\Helper\SingleProductUpdate
     */
    protected $singleProductHelper;

    /**
     * View constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Knawat\Dropshipping\Helper\SingleProductUpdate $singleProductHelper
    ){
        $this->_coreRegistry = $registry;
        $this->singleProductHelper = $singleProductHelper;
    }


    /**
     * @param \Magento\Catalog\Controller\Product\View $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Catalog\Controller\Product\View $subject, $result)
    {
        if($result != null){
            $isKnawat = $this->getProduct()->getIsKnawat();
            if($isKnawat){
                $sku = $this->getProduct()->getSku();
                if(!empty($sku)){
                    $this->singleProductHelper->productUpdate($sku);
                }
            }
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }


}