<?php
namespace MiraklSeller\Process\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MiraklSeller\Api\Model\ResourceModel\ArraySerializableFieldsTrait;
use MiraklSeller\Process\Model\Process as ProcessModel;

class Process extends AbstractDb
{
    use ArraySerializableFieldsTrait;

    /**
     * @var array
     */
    protected $_serializableFields = [
        'params' => [null, []]
    ];

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('mirakl_seller_process', 'id');
    }

    /**
     * Perform actions before object save
     *
     * @param   AbstractModel   $object
     * @return  $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var ProcessModel $object */
        if (!$object->getHash()) {
            $object->setHash(md5($object->getType() . ' ' . $object->getName()));
        }

        if (!$object->getStatus()) {
            $object->setStatus(ProcessModel::STATUS_PENDING);
        }

        $currentTime = date('Y-m-d H:i:s');
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($currentTime);
        }
        $object->setUpdatedAt($currentTime);

        parent::_beforeSave($object);

        return $this;
    }

    /**
     * Deletes specified processes from database
     *
     * @param   array   $ids
     * @return  bool|int
     */
    public function deleteIds(array $ids)
    {
        if (!empty($ids)) {
            return $this->getConnection()->delete($this->getMainTable(), ['id IN (?)' => $ids]);
        }

        return false;
    }

    /**
     * Mark expired processes execution as TIMEOUT according to specified delay in minutes
     *
     * @param   int $delay
     * @return  int $result
     * @throws  \Exception
     */
    public function markAsTimeout($delay)
    {
        $delay = abs(intval($delay));
        if (!$delay) {
            throw new \Exception('Delay for expired processes cannot be empty');
        }

        $now = date('Y-m-d H:i:s');
        $timestampDiffExpr = new \Zend_Db_Expr(sprintf(
            "TIMESTAMPDIFF(MINUTE, created_at, '%s') > %d",
            $now,
            $delay
        ));

        $result = $this->getConnection()->update(
            $this->getMainTable(),
            [
                'status' => ProcessModel::STATUS_TIMEOUT,
                'updated_at' => $now,
            ],
            [
                'status = ?' => ProcessModel::STATUS_PROCESSING,
                strval($timestampDiffExpr) => ProcessModel::STATUS_TIMEOUT
            ]
        );

        return $result;
    }

    /**
     * Overrides this in order to not unset object that calls __destruct() otherwise
     *
     * @param   AbstractModel   $object
     * @return  array
     */
    protected function prepareDataForUpdate($object)
    {
        $data = $this->_prepareDataForTable($object, $this->getMainTable());

        return $data;
    }

    /**
     * Truncate mirakl_process table
     */
    public function truncate()
    {
        $this->getConnection()->truncateTable($this->getMainTable());
    }
}
