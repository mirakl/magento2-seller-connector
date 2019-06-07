<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Product\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class NbProductsWithErrors extends Column
{
    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param OfferResourceFactory  $offerResourceFactory
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OfferResourceFactory $offerResourceFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->offerResourceFactory = $offerResourceFactory;
    }

    /**
     * @param   int     $listingId
     * @return  string
     */
    public function getNbProductsWithErrors($listingId)
    {
        /** @var \MiraklSeller\Core\Model\ResourceModel\Offer $resource */
        $resource       = $this->offerResourceFactory->create();
        $nbErrors       = 0;
        $failedProducts = $resource->getNbListingFailedProductsByStatus($listingId);
        $failedOffers   = $resource->getNbListingFailedOffers($listingId);
        $productIds     = [];

        if (count($failedProducts)) {
            foreach ($failedProducts as $status => $failedProduct) {
                $nbErrors += $failedProduct['count'];
                $productIds += explode(',', $failedProduct['offer_product_id']);
            }

            if (!empty($failedOffers['count'])) {
                $offerProductIds = explode(',', $failedOffers['offer_product_id']);
                $nbProductsIntersect = count(array_intersect($productIds, $offerProductIds));
                $nbErrors = $nbErrors + $failedOffers['count'] - $nbProductsIntersect;
            }
        } elseif (!empty($failedOffers['count'])) {
            $nbErrors = $failedOffers['count'];
        }

        return $nbErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->getNbProductsWithErrors($item['id']);
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}