<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Tracking\Product\Column;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Product as ProductStatus;

class ImportStatus implements OptionSourceInterface
{
    /**
     * @var bool
     */
    protected $emptyValue = true;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if ($this->emptyValue) {
            $options = [
                [
                    'value' => '',
                    'label' => __('-- Please Select --'),
                ]
            ];
        } else {
            $options = [];
        }

        foreach (ProductStatus::getStatusLabels() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        return $options;
    }
}
