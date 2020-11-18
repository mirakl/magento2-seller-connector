<?php
namespace MiraklSeller\Process\Model\Process;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Process\Model\Process;

class Type implements OptionSourceInterface
{
    /**
     * Get product type labels array for option element
     *
     * @return array
     */
    public function getOptions()
    {
        $res = [];
        $types = Process::getTypes();
        sort($types);
        foreach ($types as $value) {
            $res[] = ['value' => $value, 'label' => $value];
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}