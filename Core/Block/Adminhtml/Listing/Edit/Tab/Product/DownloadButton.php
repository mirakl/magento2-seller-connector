<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit\Tab\Product;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MiraklSeller\Core\Block\Adminhtml\Listing\Edit\GenericButton;

class DownloadButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => $this->escaper->escapeHtml(__('Download Products for Mapping')),
            'title' => $this->escaper->escapeHtml(
                __("Download listing's products file and use it for the mapping in your Mirakl back office")
            ),
            'class' => 'download marketplace',
            'disabled' => $this->getListing()->isActive() ? '' : 'disabled',
            'onclick' => "setLocation('" . $this->getDownloadUrl() . "')",
            'sort_order' => 10,
        ];
    }

    /**
     * @return  string
     */
    public function getDownloadUrl()
    {
        return $this->context->getUrlBuilder()->getUrl('*/listingProduct/download', [
            'id' => $this->context->getRequest()->getParam('id'),
        ]);
    }
}
