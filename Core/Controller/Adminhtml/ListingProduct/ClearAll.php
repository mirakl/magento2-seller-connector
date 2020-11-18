<?php
namespace MiraklSeller\Core\Controller\Adminhtml\ListingProduct;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use MiraklSeller\Core\Controller\Adminhtml\Listing\AbstractListing;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;

class ClearAll extends AbstractListing
{
    /**
     * @var OfferResource
     */
    protected $offerResource;

    /**
     * @param   Context         $context
     * @param   Registry        $coreRegistry
     * @param   OfferResource   $offerResource
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        OfferResource $offerResource
    ) {
        parent::__construct($context, $coreRegistry);
        $this->offerResource = $offerResource;
    }

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
            return $resultRedirect->setPath('*/listing/');
        }

        try {
            $this->offerResource->deleteListingOffers($listing->getId());
            $this->messageManager->addSuccessMessage(__('Products cleared successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        }

        return $resultRedirect->setPath('*/listing/edit', ['id' => $listing->getId()]);
    }
}