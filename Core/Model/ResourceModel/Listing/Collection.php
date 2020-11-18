<?php
namespace MiraklSeller\Core\Model\ResourceModel\Listing;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Model\Listing;

/**
 * @method Listing getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Listing::class, \MiraklSeller\Core\Model\ResourceModel\Listing::class);
    }

    /**
     * @param   int|array   $listingIds
     * @return  $this
     */
    public function addIdFilter($listingIds)
    {
        if (empty($listingIds)) {
            return $this;
        }

        if (!is_array($listingIds)) {
            $listingIds = [$listingIds];
        }

        return $this->addFieldToFilter('id', ['in' => $listingIds]);
    }

    /**
     * @param   mixed   $connection
     * @return  $this
     */
    public function addConnectionFilter($connection)
    {
        if ($connection instanceof Connection) {
            $connection = [$connection->getId()];
        }

        if (!is_array($connection)) {
            $connection = [$connection];
        }

        $this->addFieldToFilter('connection_id', ['in' => $connection]);

        return $this;
    }

    /**
     * @param   boolean   $isActive
     * @return  $this
     */
    public function addActiveFilter($isActive = true)
    {
        return $this->addFieldToFilter('is_active', $isActive);
    }

    /**
     * Get associative array of listing as [id => name]
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->setOrder('name', \Zend_Db_Select::SQL_ASC)->_toOptionArray('id', 'name');
    }
}
