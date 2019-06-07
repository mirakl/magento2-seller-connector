<?php
namespace MiraklSeller\Process\Model\Process;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Process\Model\Process;

class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function getOptions()
    {
        $res = [];

        foreach (Process::getStatuses() as $value) {
            $res[] = ['value' => $value, 'label' => strtoupper(__($value))];
        }

        usort($res, function($a, $b) { return strcmp($a['label'], $b['label']); });

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