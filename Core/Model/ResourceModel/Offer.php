<?php
namespace MiraklSeller\Core\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MiraklSeller\Core\Model\Offer as OfferModel;

class Offer extends AbstractDb
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param  Context  $context
     * @param  DateTime $dateTime
     * @param  string   $connectionName
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_offer', 'id');
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var \MiraklSeller\Core\Model\Offer $object */
        $now = $this->dateTime->date('Y-m-d H:i:s');

        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($now);
        }
        $object->setUpdatedAt($now);

        return parent::_beforeSave($object);
    }

    /**
     * @param   int                 $listingId
     * @param   ProductCollection   $collection
     * @param   mixed               $cols
     * @return  $this
     */
    public function addOfferInfoToProductCollection($listingId, $collection, $cols = '*')
    {
        $collection->getSelect()
            ->join(
                ['offers' => $this->getMainTable()],
                "e.entity_id = offers.product_id AND offers.listing_id = {$listingId}",
                $cols
            );

        return $this;
    }

    /**
     * Inserts new offers for a specific listing and product ids
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   int     $chunkSize
     * @return  int
     * @throws  \Exception
     */
    public function createOffers($listingId, array $productIds, $chunkSize = 1000)
    {
        if (empty($productIds)) {
            return 0;
        }

        $data = [];
        $now = $this->dateTime->date('Y-m-d H:i:s');
        foreach ($productIds as $productId) {
            $data[] = [
                'listing_id'            => $listingId,
                'product_id'            => $productId,
                'product_import_status' => OfferModel::PRODUCT_NEW,
                'offer_import_status'   => OfferModel::OFFER_NEW,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        }

        try {
            $this->getConnection()->beginTransaction();

            // Split data into multiple chunks in order to not overload the MySQL server
            $chunks = array_chunk($data, $chunkSize);
            $inserted = 0;
            foreach ($chunks as $chunk) {
                $inserted += $this->getConnection()->insertMultiple($this->getMainTable(), $chunk);
            }

            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $inserted;
    }

    /**
     * Deletes listing offers with optional offer status
     *
     * @param   int     $listingId
     * @param   mixed   $offerStatus
     * @return  int
     */
    public function deleteListingOffers($listingId, $offerStatus = null)
    {
        $where = ['listing_id = ?' => $listingId];

        if ($offerStatus) {
            $where['offer_import_status IN (?)'] = (array) $offerStatus;
        }

        return $this->getConnection()->delete($this->getMainTable(), $where);
    }

    /**
     * Returns the listing products that have failed and with expired delay
     *
     * @param   int $listingId
     * @param   int $delay
     * @return  array
     */
    public function getListingFailedProductIds($listingId, $delay)
    {
        // Ensure delay is a positive number
        $delay = abs(intval($delay));

        // Check number of days between product tracking date and current date
        $timestampDiffExpr = new \Zend_Db_Expr(sprintf(
            "TIMESTAMPDIFF(DAY, tracking.updated_at, '%s') >= %d",
            $this->dateTime->date('Y-m-d H:i:s'),
            $delay
        ));

        // Retrieve products in failed status and with product tracking updated date > (now() - $delay)
        $select = $this->getConnection()->select()
            ->from(['offer' => $this->getMainTable()], 'product_id')
            ->join(
                ['tracking' => $this->getTable('mirakl_seller_listing_tracking_product')],
                'tracking.import_id = offer.product_import_id',
                ''
            )
            ->where('offer.listing_id = ?', $listingId)
            ->where('offer.product_import_status IN (?)', [
                OfferModel::PRODUCT_TRANSFORMATION_ERROR,
                OfferModel::PRODUCT_INTEGRATION_ERROR,
                OfferModel::PRODUCT_INVALID_REPORT_FORMAT,
                OfferModel::PRODUCT_NOT_FOUND_IN_REPORT
            ])
            ->where((string) $timestampDiffExpr);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getListingIdsByProductIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'listing_id')
            ->where('product_id IN (?)', $productIds);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Returns pending offers of a specific listing
     *
     * @param   int     $listingId
     * @param   int     $importId
     * @param   array   $cols
     * @return  array
     */
    public function getListingPendingOffers($listingId, $importId, $cols = ['product_id', 'id'])
    {
        $conds = [
            'offer_import_id = ?'     => $importId,
            'offer_import_status = ?' => OfferModel::OFFER_PENDING,
        ];

        return $this->getListingProducts($listingId, $conds, $cols);
    }

    /**
     * Returns pending products of a specific listing
     *
     * @param   int     $listingId
     * @param   int     $importId
     * @param   array   $cols
     * @param   array   $pendingStatus
     * @return  array
     */
    public function getListingPendingProducts(
        $listingId,
        $importId,
        $cols = ['product_id', 'id'],
        $pendingStatus = [OfferModel::PRODUCT_PENDING]
    ) {
        $conds = [
            'product_import_id = ?'         => $importId,
            'product_import_status IN (?)'  => $pendingStatus,
        ];

        return $this->getListingProducts($listingId, $conds, $cols);
    }

    /**
     * Returns all product ids of a specific listing
     *
     * @param   int             $listingId
     * @param   string|array    $offerStatus
     * @param   string|array    $productStatus
     * @return  array
     */
    public function getListingProductIds($listingId, $offerStatus = null, $productStatus = null)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['product_id'])
            ->where('listing_id = ?', $listingId);

        if (!empty($offerStatus)) {
            $select->where('offer_import_status IN (?)', (array) $offerStatus);
        }

        if (!empty($productStatus)) {
            $select->where('product_import_status IN (?)', (array) $productStatus);
        }

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Returns products of a specific listing
     *
     * @param   int     $listingId
     * @param   array   $where
     * @param   mixed   $cols
     * @return  array
     */
    public function getListingProducts($listingId, array $where = [], $cols = '*')
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), $cols)
            ->where('listing_id = ?', $listingId);

        foreach ($where as $cond => $value) {
            $select->where($cond, $value);
        }

        return $this->getConnection()->fetchAssoc($select);
    }

    /**
     * Returns the number of listing products that have success status
     *
     * @param   int $listingId
     * @return  int
     */
    public function getNbListingSuccessProducts($listingId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), '')
            ->columns(['count' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('listing_id = ?', $listingId)
            ->where('product_import_status = ?', OfferModel::PRODUCT_SUCCESS);

        return (int) $this->getConnection()->fetchOne($select);
    }

    /**
     * Returns the listing products that have failed group by product status
     *
     * @param   int $listingId
     * @return  array
     */
    public function getNbListingFailedProductsByStatus($listingId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), '')
            ->columns('product_import_status')
            ->columns(['count' => new \Zend_Db_Expr('COUNT(*)')])
            ->columns(['offer_product_id' => new \Zend_Db_Expr('GROUP_CONCAT(product_id)')])
            ->where('listing_id = ?', $listingId)
            ->where('product_import_status IN (?)', [
                OfferModel::PRODUCT_TRANSFORMATION_ERROR,
                OfferModel::PRODUCT_INTEGRATION_ERROR,
                OfferModel::PRODUCT_INVALID_REPORT_FORMAT,
                OfferModel::PRODUCT_NOT_FOUND_IN_REPORT,
            ])
            ->group('product_import_status');

        return $this->getConnection()->fetchAssoc($select);
    }

    /**
     * Returns the listing products that have failed group by product status
     *
     * @param   int $listingId
     * @return  array
     */
    public function getNbListingFailedOffers($listingId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), '')
            ->columns(['count' => new \Zend_Db_Expr('COUNT(*)')])
            ->columns(['offer_product_id' => new \Zend_Db_Expr('GROUP_CONCAT(product_id)')])
            ->where('listing_id = ?', $listingId)
            ->where('offer_import_status = ?', OfferModel::OFFER_ERROR);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     * Marks offers as DELETE for a specific listing and product ids
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @return  int
     */
    public function markOffersAsDelete($listingId, array $productIds)
    {
        if (empty($productIds)) {
            return 0;
        }

        // Remove physically offers that have never been exported
        $this->getConnection()->delete($this->getMainTable(), [
            'listing_id = ?'    => $listingId,
            'product_id IN (?)' => $productIds,
            'offer_import_id IS NULL',
        ]);

        return $this->updateProducts($listingId, $productIds, [
            'offer_import_id'     => null,
            'offer_import_status' => OfferModel::OFFER_DELETE,
        ]);
    }

    /**
     * Marks offers as NEW for a specific listing and product ids
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @return  int
     */
    public function markOffersAsNew($listingId, array $productIds)
    {
        return $this->updateProducts($listingId, $productIds, [
            'offer_import_id'     => null,
            'offer_import_status' => OfferModel::OFFER_NEW,
            'offer_error_message' => null,
        ]);
    }

    /**
     * Marks offers as PENDING for a specific listing, product ids and offers import id
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   int     $importId
     * @return  int
     */
    public function markOffersAsPending($listingId, array $productIds, $importId)
    {
        return $this->updateProducts($listingId, $productIds, [
            'offer_import_id'     => $importId,
            'offer_import_status' => OfferModel::OFFER_PENDING,
            'offer_error_message' => null,
        ]);
    }

    /**
     * Marks specified products as NEW for a specific listing
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @return  int
     */
    public function markProductsAsNew($listingId, array $productIds)
    {
        return $this->updateProducts($listingId, $productIds, [
            'product_import_id'      => null,
            'product_import_message' => null,
            'product_import_status'  => OfferModel::PRODUCT_NEW,
        ]);
    }

    /**
     * Marks products as PENDING for a specific listing, product ids and products import id
     *
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   int     $importId
     * @return  int
     */
    public function markProductsAsPending($listingId, array $productIds, $importId)
    {
        return $this->updateProducts($listingId, $productIds, [
            'product_import_id'      => $importId,
            'product_import_message' => null,
            'product_import_status'  => OfferModel::PRODUCT_PENDING,
        ]);
    }

    /**
     * @param   array   $data
     * @param   mixed   $where
     * @return  int
     */
    public function update(array $data, $where = '')
    {
        return $this->getConnection()->update($this->getMainTable(), $data, $where);
    }

    /**
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   string  $status
     * @return  int
     */
    public function updateOffersStatus($listingId, array $productIds, $status)
    {
        return $this->updateProducts($listingId, $productIds, ['offer_import_status' => $status]);
    }

    /**
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   string  $status
     * @return  int
     */
    public function updateProductsStatus($listingId, array $productIds, $status)
    {
        return $this->updateProducts($listingId, $productIds, ['product_import_status' => $status]);
    }

    /**
     * @param   int     $listingId
     * @param   array   $productIds
     * @param   array   $data
     * @param   int     $chunkSize
     * @return  int
     * @throws  \Exception
     */
    public function updateProducts($listingId, array $productIds, array $data, $chunkSize = 1000)
    {
        if (empty($productIds)) {
            return 0;
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $this->dateTime->date('Y-m-d H:i:s');
        }

        try {
            $this->getConnection()->beginTransaction();

            // Split product ids into multiple chunks in order to not overload the MySQL server
            $chunks = array_chunk($productIds, $chunkSize);
            $updated = 0;
            foreach ($chunks as $chunk) {
                $updated += $this->update($data, [
                    'listing_id = ?'    => $listingId,
                    'product_id IN (?)' => $chunk,
                ]);
            }

            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $updated;
    }

    /**
     * @param   array   $data
     * @param   int     $chunkSize
     * @return  int
     * @throws  \Exception
     */
    public function updateMultiple(array $data, $chunkSize = 1000)
    {
        if (empty($data)) {
            return 0;
        }

        try {
            $this->getConnection()->beginTransaction();

            // Split data into multiple chunks in order to not overload the MySQL server
            $chunks = array_chunk($data, $chunkSize);
            $updated = 0;
            foreach ($chunks as $chunk) {
                $updated += $this->getConnection()->insertOnDuplicate($this->getMainTable(), $chunk);
            }

            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $updated;
    }
}
