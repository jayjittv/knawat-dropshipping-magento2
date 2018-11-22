<?php

namespace Knawat\Dropshipping\Controller\Adminhtml\Dropshipping;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Edit
 * @package Knawat\Dropshipping\Controller\Adminhtml\Dropshipping
 */
class Edit extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $configInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * knawat default configuration path value
     * @var string
     */
    protected $pathPrefix = 'knawat/store/';

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->configInterface = $configInterface;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * save and update setting tab's information
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            unset($params['form_key']);
            foreach ($params as $key => $value) {
                $this->setConfig($this->pathPrefix.$key, $value);
            }
            $this->messageManager->addSuccessMessage(__('Settings has been saved.'));
            $this->_redirect('dropshipping/dropshipping/index');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving this Settings') . ' ' . $e->getMessage());
        }
    }

    /**
     * @param $path
     * @param $value
     * @return \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected function setConfig($path, $value)
    {
        return $this->configInterface->saveConfig($path, $value, 'default', 0);
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knawat_Dropshipping::edit');
    }
}
