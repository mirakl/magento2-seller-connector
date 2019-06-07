<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use MiraklSeller\Core\Controller\Adminhtml\RawMessagesTrait;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Model\Listing;

abstract class AbstractExport extends AbstractListing
{
    use RawMessagesTrait;

    /**
     * @var ListingHelper
     */
    protected $listingHelper;

    /**
     * @param   Context         $context
     * @param   Registry        $coreRegistry
     * @param   ListingHelper   $listingHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ListingHelper $listingHelper
    ) {
        parent::__construct($context, $coreRegistry);
        $this->listingHelper = $listingHelper;
    }

    /**
     * @param   string  $type
     * @param   bool    $offerFull
     * @param   string  $productMode
     * @return  Redirect
     */
    protected function _exportAction(
        $type,
        $offerFull = true,
        $productMode = Listing::PRODUCT_MODE_PENDING
    ) {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $listing = $this->getListing(true);
        } catch (NotFoundException $e) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $processes = $this->listingHelper->export($listing, $type, $offerFull, $productMode);

            if (count($processes) === 1) {
                $url = $processes[0]->getUrl();
            } else {
                $url = $this->getUrl('*/mirakl_seller_process/list');
            }

            $this->messageManager->addSuccessMessage(__('The process to export the listing will be executed in parallel.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $url));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        }

        return $resultRedirect->setPath('*/*/edit', ['id' => $listing->getId()]);
    }
}