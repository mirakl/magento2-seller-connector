<?php
namespace MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer;

use MiraklSeller\Core\Model\Listing\Tracking\Offer;
use MiraklSeller\Core\Model\Listing\Tracking\Status\Offer as OfferStatus;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Offer::class, \MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer::class);
        $this->_idFieldName = 'id';
    }

    /**
     * @return  $this
     */
    public function addExcludeOfferStatusCompleteFilter()
    {
        return $this->addFieldToFilter('import_status', [
            ['nin' => OfferStatus::getCompleteStatuses()],
            ['null' => true],
        ]);
    }
}
