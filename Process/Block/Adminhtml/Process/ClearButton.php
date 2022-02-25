<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MiraklSeller\Process\Block\Adminhtml\Process\View\GenericButton;

class ClearButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $confirm = __('Are you sure? This will delete all existing processes.');
        $data = [
            'label'    => __('Clear All'),
            'class'    => 'primary',
            'on_click' => sprintf("deleteConfirm('%s', '%s')",
                $this->context->getEscaper()->escapeJs($confirm), $this->getClearAllUrl()),
        ];

        return $data;
    }

    /**
     * @return  string
     */
    protected function getClearAllUrl()
    {
        return $this->getUrl('*/*/clearAllHistory');
    }
}
