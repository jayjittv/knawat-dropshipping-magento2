<?php
namespace Knawat\Dropshipping\Block;

use Magento\Sales\Model\Order\Address;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

/**
 * Class Knawatinvoice
 * @package Knawat\Dropshipping\Block
 */
class Knawatinvoice extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;
    /**
     * Knawatinvoice constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param AddressRenderer $addressRenderer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\RequestInterface $request,
        AddressRenderer $addressRenderer,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $item = [];
        $data = $this->getRequest()->getParams();
        try {
            foreach ($data as $key => $value) {
                $dataArray = explode("-", $key);
            }
            foreach ($dataArray as $value) {
                $value = base64_decode($value);
                $item[] = $value;
            }
            if (array_key_exists(0, $item)) {
                return $this->orderFactory->create()->load($item[1]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong ') . ' ' . $e->getMessage());
        }
    }
    /**
     * Get html of invoice totals block
     *
     * @param   \Magento\Sales\Model\Order\Invoice $invoice
     * @return  string
     */
    public function getInvoiceTotalsHtml($invoice)
    {
        $html = '';
        $totals = $this->getChildBlock('invoice_totals');
        if ($totals) {
            $totals->setInvoice($invoice);
            $html = $totals->toHtml();
        }
        return $html;
    }

    /**
     * Get html of invoice comments block
     *
     * @param   \Magento\Sales\Model\Order\Invoice $invoice
     * @return  string
     */
    public function getInvoiceCommentsHtml($invoice)
    {
        $html = '';
        $comments = $this->getChildBlock('invoice_comments');
        if ($comments) {
            $comments->setEntity($invoice)->setTitle(__('About Your Invoice'));
            $html = $comments->toHtml();
        }
        return $html;
    }

    /**
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }
}
