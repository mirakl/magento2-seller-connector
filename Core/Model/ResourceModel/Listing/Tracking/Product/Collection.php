<?php
namespace MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product;

use MiraklSeller\Core\Model\Listing\Tracking\Product;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Product as ProductStatus;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Product::class, \MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product::class);
        $this->_idFieldName = 'id';
    }

    /**
     * @return  $this
     */
    public function addExcludeProductStatusFinalFilter()
    {
        return $this->addFieldToFilter('import_status', [
            ['nin' => ProductStatus::getFinalStatuses()],
            ['null' => true],
        ]);
    }

    /**
     * Get last product tracking from listing sorted by creation date and not in a final status
     *
     * @param   int     $listingId
     * @return  $this
     */
    public function getLastProductTrackingForListing($listingId)
    {
        $this->addExcludeProductStatusFinalFilter();
        $this->getSelect()->order('created_at ' . \Zend_Db_Select::SQL_DESC);
        $this->getSelect()->limit(1);

        return $this->addListingFilter($listingId);
    }
}
