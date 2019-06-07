<?php
namespace MiraklSeller\Sales\Model\MiraklOrder\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Sales\Helper\Data as Helper;

class Status implements OptionSourceInterface
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param   Helper  $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $this->options = [];
            foreach ($this->helper->getOrderStatusList() as $value => $label) {
                $this->options[] = [
                    'value' => $value,
                    'label' => (string) $label,
                ];
            }
        }

        return $this->options;
    }
}
