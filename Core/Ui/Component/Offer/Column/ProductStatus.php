<?php
namespace MiraklSeller\Core\Ui\Component\Offer\Column;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Core\Model\Offer;

class ProductStatus implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [];

        foreach (Offer::getProductStatusLabels() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        return $options;
    }
}
