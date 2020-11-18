<?php
namespace MiraklSeller\Core\Model\ResourceModel\Listing\Tracking;

abstract class AbstractCollection
    extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @param   int|array   $trackingIds
     * @return  $this
     */
    public function addIdFilter($trackingIds)
    {
        if (empty($trackingIds)) {
            $trackingIds = [0];
        }

        if (!is_array($trackingIds)) {
            $trackingIds = [$trackingIds];
        }

        return $this->addFieldToFilter('id', ['in' => $trackingIds]);
    }

    /**
     * @param   int $listingId
     * @return  $this
     */
    public function addListingFilter($listingId)
    {
        return $this->addFieldToFilter('listing_id', $listingId);
    }

    /**
     * @return  $this
     */
    public function addWithImportIdFilter()
    {
        return $this->addFieldToFilter('import_id', ['gt' => 0]);
    }
}
