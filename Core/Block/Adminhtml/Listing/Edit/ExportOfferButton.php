<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ExportOfferButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => $this->escaper->escapeHtml(__('3. Export Prices & Stocks')),
            'title' => $this->escaper->escapeHtml(__("Will export the listing's offers to Mirakl")),
            'class' => 'export_product marketplace',
            'disabled' => $this->getListing()->isActive() ? '' : 'disabled',
            'on_click' => sprintf(
                <<<EOL
require(['uiRegistry'], function (uiRegistry) {
    uiRegistry.get('mirakl_seller_listing_form.areas', function (element) {
        element.validate();
        if (!element.additionalInvalid && !element.source.get('params.invalid')) {
            deleteConfirm('%s', '%s#product_content');
        }
    })
});
EOL
                ,
                $this->escaper->escapeJs(
                    __('Are you sure you want to export prices & stocks for this listing?')
                ),
                $this->getExportOfferUrl()
            ),
            'sort_order' => 46,
        ];
    }

    /**
     * @return string
     */
    public function getExportOfferUrl()
    {
        return $this->getUrl('*/*/offerExport', ['id' => $this->getListingId()]);
    }
}
