<?php
namespace Knawat\Dropshipping\Cron;

/**
 * Class KnawatHealthCheck
 * @package Knawat\Dropshipping\Cron
 */
class KnawatHealthCheck
{
    /**
     * @var \Knawat\Dropshipping\Helper\BackgroundImport
     */
    protected $backgroundHelper;

    /**
     * KnawatHealthCheck constructor.
     * @param \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
    ) {

        $this->backgroundHelper = $backgroundHelper;
    }

    /**
     * Execute import process.
     */
    public function execute()
    {
        $this->backgroundHelper->cronHealthCheck();
    }
}
