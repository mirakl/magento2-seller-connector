<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class StopButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $process = $this->getProcess();

        if ($process && $process->canStop()) {
            $data = [
                'label' => __('Stop'),
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to do this?'),
                    $this->getStopUrl()
                ),
                'sort_order' => 30,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getStopUrl()
    {
        return $this->getUrl('*/*/stop', ['id' => $this->getProcessId()]);
    }
}
