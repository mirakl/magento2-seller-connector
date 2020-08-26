<?php
namespace MiraklSeller\Sales\Model\MiraklOrder\Source;

use Mirakl\MMP\Common\Domain\Order\State\OrderStatus;

class StatusImport extends Status
{
    /**
     * @var string[]
     */
    protected $_allowedStatuses = [
        OrderStatus::SHIPPING,
        OrderStatus::SHIPPED,
        OrderStatus::TO_COLLECT,
        OrderStatus::RECEIVED,
        OrderStatus::CLOSED,
    ];

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $this->options = [];
            foreach ($this->helper->getOrderStatusList() as $value => $label) {
                if (in_array($value, $this->_allowedStatuses)) {
                    $this->options[] = [
                        'value' => $value,
                        'label' => (string) $label,
                    ];
                }
            }
        }

        return $this->options;
    }
}
