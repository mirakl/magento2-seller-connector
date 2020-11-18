<?php
namespace MiraklSeller\Sales\Model\MiraklOrder\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Boolean implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'true',
                'label' => __('Yes'),
            ],
            [
                'value' => 'false',
                'label' => __('No'),
            ],
        ];
    }
}
