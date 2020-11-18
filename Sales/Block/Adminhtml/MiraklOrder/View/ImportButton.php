<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ImportButton extends AbstractButton implements ButtonProviderInterface
{
    /**
     * @return  bool
     */
    protected function canImport()
    {
        return !$this->getMagentoOrder()
            && $this->orderHelper->canImport($this->getMiraklOrder()->getStatus()->getState());
    }

    /**
     * @return  array
     */
    public function getButtonData()
    {
        $confirmationMessage = $this->getEscaper()->escapeJs(__('Are you sure you want to import this order in Magento?'));
        $data = [
            'label'      => __('Import Order'),
            'class'      => 'import',
            'on_click'   => "confirmSetLocation('{$confirmationMessage}', '{$this->getImportUrl()}')",
            'disabled'   => !$this->canImport(),
            'sort_order' => 20,
        ];

        return $data;
    }

    /**
     * @return  string
     */
    public function getImportUrl()
    {
        return $this->getUrlBuilder()->getUrl('*/*/import', [
            'connection_id' => $this->getConnection()->getId(),
            'order_id'      => $this->getMiraklOrder()->getId(),
        ]);
    }
}
