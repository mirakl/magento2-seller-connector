<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class RunButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $process = $this->getProcess();

        if ($process && $process->canRun()) {
            $data = [
                'label' => __('Run'),
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to do this?'),
                    $this->getRunUrl()
                ),
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getRunUrl()
    {
        return $this->getUrl('*/*/run', ['id' => $this->getProcessId()]);
    }
}
