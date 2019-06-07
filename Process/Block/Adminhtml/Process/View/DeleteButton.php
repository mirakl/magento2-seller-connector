<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];

        if ($this->getProcess()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'primary',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to do this?'),
                    $this->getDeleteUrl()
                ),
                'sort_order' => 40,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getProcessId()]);
    }
}
