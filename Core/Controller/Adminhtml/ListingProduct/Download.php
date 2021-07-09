<?php
namespace MiraklSeller\Core\Controller\Adminhtml\ListingProduct;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Registry;
use MiraklSeller\Core\Controller\Adminhtml\Listing\AbstractListing;
use MiraklSeller\Core\Model\Listing;

class Download extends AbstractListing
{
    /**
     * @var Listing/Download
     */
    protected $downloader;

    /**
     * @param   Context             $context
     * @param   Registry            $coreRegistry
     * @param   Listing\Download    $downloader
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Listing\Download $downloader
    ) {
        parent::__construct($context, $coreRegistry);
        $this->downloader = $downloader;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $listing = $this->getListing(true);

            $contents = $this->downloader->prepare($listing);
            $fileName = sprintf('listing_products_%d.%s', $listing->getId(), $this->downloader->getFileExtension());

            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', 'application/octet-stream', true)
                ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
            $result->setContents($contents);

            $this->_session->writeClose();

            return $result;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->_redirect('*/listing/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
    }
}
