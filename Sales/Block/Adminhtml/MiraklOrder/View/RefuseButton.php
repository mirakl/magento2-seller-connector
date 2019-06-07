<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Mirakl\MMP\Common\Domain\Order\OrderState;

class RefuseButton extends AbstractButton implements ButtonProviderInterface
{
    /**
     * @return  bool
     */
    protected function canRefuse()
    {
        return !$this->getMagentoOrder()
            && $this->getMiraklOrder()->getStatus()->getState() == OrderState::WAITING_ACCEPTANCE;
    }

    /**
     * @return  array
     */
    public function getButtonData()
    {
        if (!$this->canRefuse()) {
            return [];
        }

        $confirmationMessage = $this->getEscaper()
            ->escapeJs(__('Are you sure you want to refuse this order in Mirakl?'));
        $data = [
            'label'      => __('Refuse Order'),
            'class'      => 'primary',
            'on_click'   => "confirmSetLocation('{$confirmationMessage}', '{$this->getRefuseUrl()}')",
            'sort_order' => 25,
        ];

        return $data;
    }

    /**
     * @return  string
     */
    public function getRefuseUrl()
    {
        return $this->getUrlBuilder()->getUrl('*/*/refuse', [
            'connection_id' => $this->getConnection()->getId(),
            'order_id'      => $this->getMiraklOrder()->getId(),
        ]);
    }
}
