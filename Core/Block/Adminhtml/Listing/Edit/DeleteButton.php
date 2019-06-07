<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getListingId()) {
            $data = [
                'label' => __('Delete Listing'),
                'class' => 'delete',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    $this->escaper->escapeJs(__('Are you sure you want to delete this Mirakl listing?')),
                    $this->getDeleteUrl()
                ),
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getListingId()]);
    }
}
