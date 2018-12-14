<?php
namespace Knawat\Dropshipping\Cron;

/**
 * Class KnawatImport
 * @package Knawat\Dropshipping\Cron
 */
class KnawatImport
{

    /**
     * @var \Knawat\Dropshipping\Helper\CommonHelper
     */
    protected $commonHelper;

    /**
     * KnawatImport constructor.
     * @param \Knawat\Dropshipping\Helper\CommonHelper $commonhelper
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\CommonHelper $commonHelper
    ) {

        $this->commonHelper = $commonHelper;
    }

    /**
     *execute product import with cron
     */
    public function execute()
    {
        $this->commonHelper->runImport();
    }
}
