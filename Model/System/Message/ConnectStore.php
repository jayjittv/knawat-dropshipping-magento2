<?php
namespace Knawat\Dropshipping\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

/**
 * Class ConnectStore
 * @package Knawat\Dropshipping\Model\System\Message
 */
class ConnectStore implements MessageInterface
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var
     */
    protected $configHelper;
    /**
     * ConnectStore constructor.
     * @param \Knawat\Dropshipping\Helper\ManageConfig $confighelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\ManageConfig $confighelper,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->confighelper = $confighelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'knawat_system_message';

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if ($this->confighelper->isKnawatEnabled()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/knawat');
        if($this->confighelper->getToken()){
            return __('Your Store is successfully connected to Knawat.');
        }else{
            if ($this->confighelper->checkKeyNotAvailable()) {
                return __('Please <a href="%1">Enter</a> Consumer Key and Consumer Secret Key to connect your store to Knawat, Please go to <a href="%1">Settings</a> to add keys.', $url);
            } else {
                return __('Your store is not connected to Knawat, Please <a href="%1">Enter</a> valid consumer key and secret key from <a href="%1">Settings</a>.', $url);
            }
        }
    }

    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
