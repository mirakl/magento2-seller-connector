<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ExportProductButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => $this->escaper->escapeHtml(__('2. Export Products')),
            'title' => $this->escaper->escapeHtml(
                __("This action will export the listing's products to Mirakl")
            ),
            'class' => 'export_product marketplace',
            'disabled' => $this->getListing()->isActive() ? '' : 'disabled',
            'on_click' => "jQuery('#export-product-mode-template').modal('openModal');",
            'sort_order' => 43,
        ];
    }
}
