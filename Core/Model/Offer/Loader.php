<?php
namespace MiraklSeller\Core\Model\Offer;

use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class Loader
{
    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @param   OfferResourceFactory    $offerResourceFactory
     */
    public function __construct(OfferResourceFactory $offerResourceFactory)
    {
        $this->offerResourceFactory = $offerResourceFactory;
    }

    /**
     * Load product ids into offers table for a specific listing
     *
     * @param   int     $listingId
     * @param   array   $productIds
     */
    public function load($listingId, array $productIds)
    {
        /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $offerResource **/
        $offerResource = $this->offerResourceFactory->create();
        // Retrieve listing's existing product ids
        $existingProductIds = $offerResource->getListingProductIds($listingId);

        // 1. Mark as DELETE existing products not present anymore in listing
        if (!empty($existingProductIds)) {
            $deleteProductIds = array_diff($existingProductIds, $productIds);
            if (!empty($deleteProductIds)) {
                $offerResource->markOffersAsDelete($listingId, $deleteProductIds);
            }
        }

        // 2. Insert and mark as NEW products that do not already have offers
        $newProductIds = array_diff($productIds, $existingProductIds);
        if (!empty($newProductIds)) {
            $offerResource->createOffers($listingId, $newProductIds);
        }

        // 3. Mark as NEW existing offers that were in error or marked as delete and that are still present in listing
        $offerStatuses = [
            Offer::OFFER_ERROR,
            Offer::OFFER_DELETE,
        ];
        $existingProductIds = $offerResource->getListingProductIds($listingId, $offerStatuses);
        $updateProductIds = array_intersect($existingProductIds, $productIds);
        if (!empty($updateProductIds)) {
            $offerResource->markOffersAsNew($listingId, $updateProductIds);
        }

        // 4. Mark as NEW existing products that were in error and that are still present in listing
        $productErrorStatuses = Offer::getProductErrorStatuses();
        $existingProductIdsInError = $offerResource->getListingProductIds(
            $listingId, null, $productErrorStatuses
        );
        $updateProductIds = array_intersect($existingProductIdsInError, $productIds);
        if (!empty($updateProductIds)) {
            $offerResource->markProductsAsNew($listingId, $updateProductIds);
        }
    }
}