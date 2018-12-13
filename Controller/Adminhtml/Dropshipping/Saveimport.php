<?php

namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Saveimport
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Saveimport extends \Magento\Backend\App\Action
{
    /**
     * @var \Knawat\Dropshipping\Helper\ProductImport
     */
    protected $importer;

    /**
     * @var \Knawat\Dropshipping\Helper\BackgroundProcess
     */
    protected $backgroundHelper;

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        \Knawat\Dropshipping\Helper\ProductImport $importer,
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
    ) {
        parent::__construct($context);
        $this->importer = $importer;
        $this->backgroundHelper = $backgroundHelper;
    }

    /**
     * save and update import tab's information
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $product_batch_size = $this->importer->getConfigData('product_batch_size');
            if (empty($product_batch_size) || $product_batch_size < 0 || $product_batch_size > 100) {
                $product_batch_size = 25;
            }
            $data = [];
            $data['limit'] = $product_batch_size;
            $this->backgroundHelper->pushToQueue($data)->dispatch();
        } catch (\Exception $e) {
            // ignore.
        }
        $this->_redirect('dropshipping/dropshipping/import/');
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knawat_Dropshipping::saveimport');
    }
}
