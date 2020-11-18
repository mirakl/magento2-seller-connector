<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use MiraklSeller\Core\Controller\Adminhtml\RawMessagesTrait;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Process\Model\Process;

class Refresh extends AbstractListing
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

        $this->refreshProducts($listing);

        return $resultRedirect->setPath('*/*/edit', ['id' => $listing->getId()]);
    }

    /**
     * Refresh listing products
     *
     * @param   Listing $listing
     * @param   bool    $synchronous
     */
    protected function refreshProducts(Listing $listing, $synchronous = false)
    {
        try {
            /** @var Process $process */
            $process = $this->listingHelper->refresh($listing);

            if ($synchronous) {
                $process->run();
                $this->messageManager->addSuccessMessage(__('The list of Products / Prices & Stocks has been refreshed'));
            } else {
                $this->messageManager->addSuccessMessage(__('The process to refresh the listing will be executed in parallel.'));
            }
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        }
    }
}