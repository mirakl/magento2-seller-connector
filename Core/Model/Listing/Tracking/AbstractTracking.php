<?php
namespace MiraklSeller\Core\Model\Listing\Tracking;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\ResourceModel\ListingFactory as ListingResourceFactory;

/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  int     getListingId()
 * @method  $this   setListingId(int $listingId)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 */
abstract class AbstractTracking extends AbstractModel
{
    /**
     * @var ListingFactory
     */
    protected $listingFactory;

    /**
     * @var ListingResourceFactory
     */
    protected $listingResourceFactory;

    /**
     * @var Listing
     */
    protected $listing;

    /**
     * @param   Context                 $context
     * @param   Registry                $registry
     * @param   ListingFactory          $listingFactory
     * @param   ListingResourceFactory  $listingResourceFactory
     * @param   AbstractResource|null   $resource
     * @param   AbstractDb|null         $resourceCollection
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ListingFactory $listingFactory,
        ListingResourceFactory $listingResourceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->listingFactory = $listingFactory;
        $this->listingResourceFactory = $listingResourceFactory;
    }

    /**
     * @return  Listing
     */
    public function getListing()
    {
        if (null === $this->listing) {
            $this->listing = $this->listingFactory->create();
            $this->listingResourceFactory->create()->load($this->listing, $this->getListingId());
        }

        return $this->listing;
    }
}
