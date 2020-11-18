<?php
namespace MiraklSeller\Core\Model\ResourceModel\Offer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MiraklSeller\Core\Model\Offer;

/**
 * @method Offer getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Offer::class, \MiraklSeller\Core\Model\ResourceModel\Offer::class);
    }
}
