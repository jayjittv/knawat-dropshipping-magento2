<?php
namespace Knawat\Dropshipping\Cron;

/**
 * Class ParamUpdate
 * @package Knawat\Dropshipping\Cron
 */
class ParamUpdate
{

    /**
     * @var \Knawat\Dropshipping\Helper\CommonHelper
     */
    protected $commonhelper;

    /**
     * ParamUpdate constructor.
     * @param \Knawat\Dropshipping\Helper\CommonHelper $commonhelper
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\CommonHelper $commonhelper
    ) {

        $this->commonhelper = $commonhelper;
    }

    /**
     *execute product import with cron
     */
    public function execute()
    {
        $this->commonHelper->runImport();
    }
}