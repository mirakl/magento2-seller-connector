<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CancelButton extends AbstractButton implements ButtonProviderInterface
{
    /**
     * @return  array
     */
    public function getButtonData()
    {
        $confirmationMessage = $this->getEscaper()->escapeJs(__('Are you sure you want to cancel this order in Mirakl?'));
        $data = [
            'label'      => __('Cancel Order'),
            'class'      => 'cancel',
            'on_click'   => "confirmSetLocation('{$confirmationMessage}', '{$this->getCancelUrl()}')",
            'disabled'   => !$this->getMiraklOrder()->getData('can_cancel'),
            'sort_order' => 15,
        ];

        return $data;
    }

    /**
     * @return  string
     */
    public function getCancelUrl()
    {
        return $this->getUrlBuilder()->getUrl('*/*/cancel', [
            'connection_id' => $this->getConnection()->getId(),
            'order_id'      => $this->getMiraklOrder()->getId(),
        ]);
    }
}
