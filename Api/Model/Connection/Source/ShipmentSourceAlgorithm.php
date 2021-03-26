<?php
namespace MiraklSeller\Api\Model\Connection\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmInterface;
use Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface;

class ShipmentSourceAlgorithm implements OptionSourceInterface
{
    /**
     * @var GetSourceSelectionAlgorithmListInterface
     */
    private $getSourceSelectionAlgorithmList;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList
     */
    public function __construct(GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList)
    {
        $this->getSourceSelectionAlgorithmList = $getSourceSelectionAlgorithmList;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [
            [
                'value' => '',
                'label' => __('-- Please Select --'),
            ],
        ];

        $algorithmsList = $this->getSourceSelectionAlgorithmList->execute();

        foreach ($algorithmsList as $algorithm) {
            /** @var SourceSelectionAlgorithmInterface $algorithm */
            $this->options[] = [
                'value' => $algorithm->getCode(),
                'label' => $algorithm->getTitle(),
            ];
        }

        return $this->options;
    }
}
