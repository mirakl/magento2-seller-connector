<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;

class Delete extends AbstractListing
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $listing = $this->getListing(true);
        } catch (NotFoundException $e) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->_objectManager->get(ListingResource::class)
                ->delete($listing);

            $this->messageManager->addSuccessMessage(__('The listing has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
