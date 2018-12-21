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
     * @var
     */
    protected $configHelper;

    /**
     * ConnectStore constructor.
     * @param \Knawat\Dropshipping\Helper\ManageConfig $confighelper
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\ManageConfig $confighelper
    ) {
        $this->confighelper = $confighelper;
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
            if ($this->confighelper->checkKeyNotAvailable()) {
                return true;
            }
            $mp = $this->confighelper->createMP();
            if (!empty($mp)) {
                $token= $mp->getAccessToken();
                if ($token == '') {
                    return true;
                }
            }
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
        if ($this->confighelper->checkKeyNotAvailable()) {
            return __('Please Enter Consumer Key and Consumer Secret Key to connect your store to Knawat.');
        } else {
            return __('Your store is not connected to knawat, Please Enter valid consumer key and secret key');
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
