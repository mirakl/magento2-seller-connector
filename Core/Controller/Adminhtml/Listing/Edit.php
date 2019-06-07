<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractListing
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $listing = $this->getListing();

        $this->_coreRegistry->register('mirakl_seller_listing', $listing);

        if ($connectionId = $this->getRequest()->getParam('connection_id')) {
            $listing->setConnectionId($connectionId);
        }

        $this->_eventManager->dispatch('mirakl_seller_prepare_listing_edit', [
            'controller'  => $this,
            'listing'     => $listing,
        ]);

        $title = $listing->getId()
            ? __("Edit Listing '%1'", $listing->getName())
            : __('New Listing');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage)->addBreadcrumb($title, $title);
        $resultPage->getConfig()->getTitle()->prepend(__('Listings'));
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
