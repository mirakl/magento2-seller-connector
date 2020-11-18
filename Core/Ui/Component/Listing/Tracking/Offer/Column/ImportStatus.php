<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Tracking\Offer\Column;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Offer as OfferStatus;

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

        foreach (OfferStatus::getStatusLabels() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label),
            ];
        }

        return $options;
    }
}
