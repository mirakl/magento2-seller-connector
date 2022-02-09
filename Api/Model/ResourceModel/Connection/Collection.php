<?php
namespace MiraklSeller\Api\Model\ResourceModel\Connection;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;
use MiraklSeller\Api\Model\Connection;

/**
 * @method Connection getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Connection::class, \MiraklSeller\Api\Model\ResourceModel\Connection::class);
    }

    /**
     * @param   int|array   $connectionIds
     * @return  $this
     */
    public function addIdFilter($connectionIds)
    {
        if (empty($connectionIds)) {
            return $this;
        }

        if (!is_array($connectionIds)) {
            $connectionIds = [$connectionIds];
        }

        return $this->addFieldToFilter('id', ['in' => $connectionIds]);
    }

    /**
     * @param   mixed   $store
     * @return  $this
     */
    public function addStoreFilter($store)
    {
        if ($store instanceof Store) {
            $store = [$store->getId()];
        }

        if (!is_array($store)) {
            $store = [$store];
        }

        $this->addFieldToFilter('store_id', ['in' => $store]);

        return $this;
    }

    /**
     * Get associative array of connection as [id => name]
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->setOrder('name', Select::SQL_ASC)->_toOptionArray('id', 'name');
    }
}
