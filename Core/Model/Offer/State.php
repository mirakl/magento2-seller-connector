<?php
namespace MiraklSeller\Core\Model\Offer;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class State implements OptionSourceInterface
{
    const DEFAULT_STATE = '11'; // 11 = 'New' by default in Mirakl

    /**
     * @var array
     */
    protected $_defaultOptions = [
        '12' => 'Broken product - Not working',
        '1'  => 'Used - Like New',
        '2'  => 'Used - Very Good Condition',
        '3'  => 'Used - Good Condition',
        '4'  => 'Used - Acceptable Condition',
        '5'  => 'Collectors - Like New',
        '6'  => 'Collectors - Very Good Condition',
        '7'  => 'Collectors - Good Condition',
        '8'  => 'Collectors - Acceptable Condition',
        '10' => 'Refurbished',
        '11' => 'New',
    ];

    /**
     * @var array
     */
    protected $options;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @param   EventManagerInterface       $eventManager
     */
    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return  array
     */
    public function getDefaultOptions()
    {
        return $this->_defaultOptions;
    }

    /**
     * @return  array
     */
    public function getOptions()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = new DataObject(['values' => $this->getDefaultOptions()]);

        $this->eventManager->dispatch('mirakl_seller_offer_state_options', [
            'options' => $options,
        ]);
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