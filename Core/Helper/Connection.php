<?php
namespace MiraklSeller\Core\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder as MiraklOrder;
use MiraklSeller\Api\Helper\AdditionalField as AdditionalFieldHelper;
use MiraklSeller\Api\Model\Connection as Model;
use MiraklSeller\Api\Model\ResourceModel\Connection as ResourceModel;
use MiraklSeller\Core\Model\ResourceModel\Listing\Collection;
use MiraklSeller\Core\Model\ResourceModel\Listing\CollectionFactory;

class Connection extends Data
{
    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var AdditionalFieldHelper
     */
    protected $additionalFieldHelper;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   ResourceModel           $resourceModel
     * @param   CollectionFactory       $collectionFactory
     * @param   AdditionalFieldHelper   $additionalFieldHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ResourceModel $resourceModel,
        CollectionFactory $collectionFactory,
        AdditionalFieldHelper $additionalFieldHelper
    ) {
        parent::__construct($context, $storeManager);
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->additionalFieldHelper = $additionalFieldHelper;
    }

    /**
     * @param   Model $connection
     * @return  Collection
     */
    public function getActiveListings(Model $connection)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addConnectionFilter($connection)
            ->addActiveFilter()
            ->setOrder('name', 'ASC');

        return $collection;
    }

    /**
     * @param   Model       $connection
     * @param   MiraklOrder $miraklOrder
     * @return  string
     */
    public function getMiraklOrderUrl(Model $connection, MiraklOrder $miraklOrder)
    {
        $url = sprintf(
            '%s/mmp/shop/order/%s',
            $connection->getBaseUrl(),
            $miraklOrder->getId()
        );

        return $url;
    }

    /**
     * Calls API AF01 and updates offer additional fields of specified connection
     *
     * @param   Model  $connection
     * @return  Model
     */
    public function updateOfferAdditionalFields(Model $connection)
    {
        $fields = $this->additionalFieldHelper->getOfferAdditionalFields($connection);
        $connection->setOfferAdditionalFields(json_encode($fields->toArray()));
        $this->resourceModel->save($connection);

        return $connection;
    }
}
