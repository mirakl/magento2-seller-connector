<?php
namespace MiraklSeller\Api\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Connection extends AbstractDb
{
    /**
     * @var array
     */
    protected $_serializableFields = [
        'carriers_mapping' => [[], []],
        'offer_additional_fields' => [[], []],
        'exportable_attributes' => [[], []],
    ];

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_connection', 'id');
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var \MiraklSeller\Api\Model\Connection $object */
        if (!$object->getShopId()) {
            $object->setShopId(null);
        }

        if (!$object->getLastOrdersSynchronizationDate() && $object->isObjectNew()) {
            $now = date('Y-m-d H:i:s');
            $object->setLastOrdersSynchronizationDate($now);
        }

        return parent::_beforeSave($object);
    }
}
