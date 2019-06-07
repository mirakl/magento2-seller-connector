<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit\Tab\Product;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MiraklSeller\Core\Block\Adminhtml\Listing\Edit\GenericButton;

class ClearAllButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => $this->escaper->escapeHtml(__('Clear Listing Products')),
            'title' => $this->escaper->escapeHtml(
                __('Immediately clear all products associated with the listing')
            ),
            'class' => 'clear_all',
            'disabled' => $this->getListing()->isActive() ? '' : 'disabled',
            'onclick' => sprintf(
                "confirmSetLocation('%s', '%s')",
                $this->escaper->escapeJs(__('Are you sure you want to clear this Mirakl listing?')),
                $this->getClearAllUrl()
            ),
            'sort_order' => 10,
        ];
    }

    /**
     * @return  string
     */
    public function getClearAllUrl()
    {
        return $this->context->getUrlBuilder()->getUrl('*/listingProduct/clearAll', [
            'id' => $this->context->getRequest()->getParam('id'),
        ]);
    }
}
