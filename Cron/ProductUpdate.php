<?php
namespace Knawat\Dropshipping\Cron;

/**
 * Class ProductUpdate
 * @package Knawat\Dropshipping\Cron
 */
class ProductUpdate
{
    /**
     * @var \Knawat\Dropshipping\Helper\BackgroundImport
     */
    protected $backgroundHelper;

    /**
     * ProductUpdate constructor.
     * @param \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
     */
    public function __construct(
        \Knawat\Dropshipping\Helper\BackgroundImport $backgroundHelper
    ) {

        $this->backgroundHelper = $backgroundHelper;
    }

    /**
     * execute import process 
     */
    public function execute()
    {
        $this->backgroundHelper->cronHealthCheck();
    }
}
