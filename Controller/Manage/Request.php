<?php
namespace Knawat\Dropshipping\Controller\Manage;

use \Magento\Framework\App\Action\Action;

class Request extends Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    protected $backgroundImport;

    protected $cache;

    protected $configFactory;

    protected $config;

    /**
     * @var \Knawat\Dropshipping\Helper\ProductImport
     */
    protected $importer;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundImport,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Config\Model\ConfigFactory $configFactory,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Knawat\Dropshipping\Helper\ProductImport $importer
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->backgroundImport = $backgroundImport;
        $this->cache = $cache;
        $this->configFactory = $configFactory;
        $this->config = $config;
        $this->importer = $importer;
        parent::__construct($context);
    }

    /**
     * Testimonials Index, shows a list of testimonials.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $key = $this->getRequest()->getParam('form_key');
        $logger = new \Zend\Log\Logger();
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/logforcurl.log');
        $logger->addWriter($writer);
        $logger->info($key);
        $logger->info("called dispatch");

        $cache = $this->cache->load('process_lock');
        $logger->info($cache);

        $this->backgroundImport->maybeHandle();
        return $this->resultPageFactory->create();
    }
}
