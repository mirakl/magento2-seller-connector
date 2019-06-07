<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class RefreshButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => $this->escaper->escapeHtml(__('1. Refresh Products')),
            'title' => $this->escaper->escapeHtml(
                __("This action will refresh the listing's products")
            ),
            'class' => 'refresh',
            'disabled' => $this->getListing()->isActive() ? '' : 'disabled',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s#product_content')",
                $this->escaper->escapeJs(
                    __("Are you sure you want to refresh this listing's products?")
                ),
                $this->getRefreshUrl()
            ),
            'sort_order' => 40,
        ];
    }

    /**
     * @return string
     */
    public function getRefreshUrl()
    {
        return $this->getUrl('*/*/refresh', ['id' => $this->getListingId()]);
    }
}
