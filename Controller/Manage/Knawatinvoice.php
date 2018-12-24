<?php
namespace Knawat\Dropshipping\Controller\Manage;

use \Magento\Framework\App\Action\Action;

/**
 * Class KnawatInvoice
 * @package Knawat\Dropshipping\Controller\Manage
 */
class KnawatInvoice extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * KnawatInvoice constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        try {
            $item = [];
            foreach ($data as $key => $value) {
                $dataArray = explode("-", $key);
            }
            foreach ($dataArray as $value) {
                $value = base64_decode($value);
                $item[] = $value;
            }

            if (array_key_exists(0, $item)) {
                $order = $this->orderFactory->create()->load($item[1]);
                $protectCode =  $order->getProtectCode();
                if (array_key_exists(2, $item)) {
                    if ($protectCode == $item[3]) {
                        return $this->resultPageFactory->create();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong ') . ' ' . $e->getMessage());
            $this->_redirect('404notfound');
        }
        $this->_redirect('404notfound');
    }
}
