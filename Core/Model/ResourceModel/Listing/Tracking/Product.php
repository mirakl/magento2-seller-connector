<?php
namespace MiraklSeller\Core\Model\ResourceModel\Listing\Tracking;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Product extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_listing_tracking_product', 'id');
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var \MiraklSeller\Core\Model\Listing\Tracking\Product $object */
        $now = date('Y-m-d H:i:s');
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($now);
        }
        $object->setUpdatedAt($now);

        return parent::_beforeSave($object);
    }

    /**
     * Deletes specified trackings from database
     *
     * @param   array   $ids
     * @return  bool|int
     */
    public function deleteIds(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        return $this->getConnection()->delete($this->getMainTable(), ['id IN (?)' => $ids]);
    }
}
