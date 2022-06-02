<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;

class OfferStateOptions implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @return  array
     */
    public function getOptions()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $listing = $this->registry->registry('mirakl_seller_listing');
        if (!$listing) {
            return [];
        }

        $stateOptions = $listing->getOfferStates();
        $options = [];
        foreach ($stateOptions as $stateOption) {
            $options[$stateOption['code']] = $stateOption['label'];
        }

        $options = new DataObject(['values' => $options]);
        $this->options = $options->getData('values');

        return $this->options;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $key => $label) {
            $options[] = [
                'label' => __($label),
                'value' => $key,
            ];
        }

        return $options;
    }
}