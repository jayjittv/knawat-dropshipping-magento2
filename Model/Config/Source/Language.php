<?php
namespace Knawat\Dropshipping\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Language
 * @package Knawat\Dropshipping\Model\Config\Source
 */
class Language implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }
        return $ret;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        $choose = [
            'en' => __('English'),
            'ar' => __('Arabic'),
            'tr' => __('Turkish')
        ];
        return $choose;
    }
}
