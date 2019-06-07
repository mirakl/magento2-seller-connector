<?php
namespace MiraklSeller\Api\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use MiraklSeller\Api\Model\Log\LogOptions;

class ApiLogging implements ArrayInterface
{
    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (LogOptions::getOptions() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => __($label),
            ];
        }

        return $options;
    }
}