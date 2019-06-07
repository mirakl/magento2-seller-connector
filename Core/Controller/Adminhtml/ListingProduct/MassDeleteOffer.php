<?php
namespace MiraklSeller\Core\Controller\Adminhtml\ListingProduct;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use MiraklSeller\Core\Controller\Adminhtml\Listing\AbstractListing;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;

class MassDeleteOffer extends AbstractListing
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context           $context
     * @param Registry          $registry
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $messages = [];
        $refresh = false;

        // MassAction Filter search 'params/namespace' as root parameter
        $this->_request->setParams(array_merge(
            $this->_request->getParam('params', []),
            $this->_request->getParams()
        ));

        $listingId = $this->getRequest()->getParam('listing_id');
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = $collection->getAllIds();

        if (empty($productIds)) {
            $messages[] = [
                'type' => 'error',
                'message' => __('Please select products.')
            ];
        } else {
            try {
                $listing = $this->getListing(true, $listingId);

                $this->_objectManager->create(OfferResource::class)
                    ->markOffersAsDelete($listing->getId(), $productIds);

                $messages[] = [
                    'type' => 'success',
                    'message' => __('Selected prices & stocks will be deleted during the next export.')
                ];
                $refresh = true;
            } catch (\Exception $e) {
                $messages[] = [
                    'type' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        /** @var Json $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultLayout->setData(['messages' => $messages, 'refresh' => $refresh]);

        return $resultLayout;
    }
}
