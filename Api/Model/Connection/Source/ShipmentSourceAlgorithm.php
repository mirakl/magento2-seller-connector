<?php
namespace MiraklSeller\Api\Model\Connection\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;

class ShipmentSourceAlgorithm implements OptionSourceInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var bool
     */
    protected $isMsiEnabled;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->isMsiEnabled  = $objectManager->get(\MiraklSeller\Core\Helper\Data::class)->isMsiEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if (!$this->isMsiEnabled) {
            return [
                [
                    'value' => '',
                    'label' => __('-- Not Applicable --'),
                ]
            ];
        }

        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [
            [
                'value' => '',
                'label' => __('-- Please Select --'),
            ],
        ];

        /** @var \Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList */
        $getSourceSelectionAlgorithmList = $this->objectManager
            ->get('Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface');
        $algorithmsList = $getSourceSelectionAlgorithmList->execute();

        foreach ($algorithmsList as $algorithm) {
            /** @var \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmInterface $algorithm */
            $this->options[] = [
                'value' => $algorithm->getCode(),
                'label' => $algorithm->getTitle(),
            ];
        }

        return $this->options;
    }
}
