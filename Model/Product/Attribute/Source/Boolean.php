<?php

namespace Knawat\Dropshipping\Model\Product\Attribute\Source;


/**
 * Class Boolean
 * @package Knawat\Dropshipping\Model\Product\Attribute\Source
 */
class Boolean extends \Magento\Eav\Model\Entity\Attribute\Source\Boolean
{

    /**
     * Retrieve all attribute options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('Only Knawat Products'), 'value' => static::VALUE_YES]
            ];
        }
        return $this->_options;
    }
}
