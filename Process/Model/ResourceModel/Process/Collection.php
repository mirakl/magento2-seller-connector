<?php
namespace MiraklSeller\Process\Model\ResourceModel\Process;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MiraklSeller\Process\Model\Process;

/**
 * @method Process getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Process::class, \MiraklSeller\Process\Model\ResourceModel\Process::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        /** @var Process $item */
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }

        return parent::_afterLoad();
    }

    /**
     * Adds API Type filter to current collection
     *
     * @return  $this
     */
    public function addApiTypeFilter()
    {
        return $this->addFieldToFilter('type', 'API');
    }

    /**
     * Adds completed status filter to current collection
     *
     * @return  $this
     */
    public function addCompletedFilter()
    {
        return $this->addStatusFilter(Process::STATUS_COMPLETED);
    }

    /**
     * Excludes processes that have the same hash as the given ones
     *
     * @param   string|array    $hash
     * @return  $this
     */
    public function addExcludeHashFilter($hash)
    {
        if (empty($hash)) {
            return $this;
        }

        if (!is_array($hash)) {
            $hash = [$hash];
        }

        return $this->addFieldToFilter('main_table.hash', ['nin' => $hash]);
    }

    /**
     * @param   int|array   $processIds
     * @return  $this
     */
    public function addIdFilter($processIds)
    {
        if (empty($processIds)) {
            return $this;
        }

        if (!is_array($processIds)) {
            $processIds = [$processIds];
        }

        return $this->addFieldToFilter('id', ['in' => $processIds]);
    }

    /**
     * @param   int $parentId
     * @return  $this
     */
    public function addParentFilter($parentId)
    {
        $this->addFieldToFilter('parent_id', $parentId);

        return $this;
    }

    /**
     * Exclude processes that have to wait for parent process to be completed
     *
     * @return  $this
     */
    public function addParentCompletedFilter()
    {
        $this->joinParent();
        $this->getSelect()
            ->where(
                'main_table.parent_id IS NULL OR parent.status = ?',
                Process::STATUS_COMPLETED
            );

        return $this;
    }

    /**
     * Adds idle status filter to current collection
     *
     * @return  $this
     */
    public function addIdleFilter()
    {
        return $this->addStatusFilter(Process::STATUS_IDLE);
    }

    /**
     * Adds pending status filter to current collection
     *
     * @return  $this
     */
    public function addPendingFilter()
    {
        return $this->addStatusFilter(Process::STATUS_PENDING);
    }

    /**
     * Adds processing status filter to current collection
     *
     * @return  $this
     */
    public function addProcessingFilter()
    {
        return $this->addStatusFilter(Process::STATUS_PROCESSING);
    }

    /**
     * Adds processing status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklProcessingFilter()
    {
        return $this->addFieldToFilter('mirakl_status', Process::STATUS_PROCESSING);
    }

    /**
     * Adds pending status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklPendingFilter()
    {
        return $this->addFieldToFilter('mirakl_status', Process::STATUS_PENDING);
    }

    /**
     * @param   string  $status
     * @return  $this
     */
    public function addStatusFilter($status)
    {
        return $this->addFieldToFilter('main_table.status', $status);
    }

    /**
     * @param   array   $cols
     * @return  $this
     */
    public function joinParent($cols = [])
    {
        $this->getSelect()
            ->joinLeft(
                ['parent' => $this->getMainTable()],
                'main_table.parent_id = parent.id',
                $cols
            );

        return $this;
    }
}
