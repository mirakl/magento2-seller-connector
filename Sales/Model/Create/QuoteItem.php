<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItemResource;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class QuoteItem
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var QuoteItemResourceFactory
     */
    protected $quoteItemResourceFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param   ProductRepositoryInterface  $productRepository
     * @param   QuoteItemResourceFactory    $quoteItemResourceFactory
     * @param   OrderHelper                 $orderHelper
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        QuoteItemResourceFactory $quoteItemResourceFactory,
        OrderHelper $orderHelper
    ) {
        $this->productRepository        = $productRepository;
        $this->quoteItemResourceFactory = $quoteItemResourceFactory;
        $this->orderHelper              = $orderHelper;
    }

    /**
     * @param   Quote           $quote
     * @param   ShopOrderLine   $orderLine
     * @return  Quote\Item
     * @throws  LocalizedException
     * @throws  NotFoundException
     */
    public function create(Quote $quote, ShopOrderLine $orderLine)
    {
        $offer = $orderLine->getOffer();
        $sku = $offer->getSku();

        try {
            // Try to find attached product in Magento
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new NotFoundException(__('Product "%1" could not be found in Magento catalog.', $sku));
        }

        $taxAmount = $this->orderHelper->getMiraklOrderLineTaxAmount($orderLine);

        $offerPrice = $offer->getPrice();
        $rowRotal = $orderLine->getPrice();

        // Prepare product for quote
        // Force the product status to 'enabled' because price is set to 0 if status is 'disabled'
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setOrigData('status', Status::STATUS_ENABLED);
        $product->setPriceCalculation(false);
        $product->setData('salable', true);
        $product->setData('price', $offerPrice);
        $product->setData('final_price', $offerPrice);
        $product->unsetData('tax_class_id');

        try {
            $quoteItem = $quote->addProduct($product, new DataObject(['qty' => $orderLine->getQuantity()]));

            // Force quote item price fields to match Mirakl order line prices
            $quoteItem->setBasePrice($offerPrice);
            $quoteItem->setPrice($offerPrice);
            $quoteItem->setOriginalPrice($offerPrice);
            $quoteItem->setBaseRowTotal($rowRotal);
            $quoteItem->setRowTotal($rowRotal);

            if ($taxAmount > 0) {
                $quoteItem->setTaxAmount($taxAmount);
                $quoteItem->setBaseTaxAmount($taxAmount);
                $quoteItem->setBaseRowTotalInclTax($quoteItem->getBaseRowTotal() + $taxAmount);
                $quoteItem->setRowTotalInclTax($quoteItem->getRowTotal() + $taxAmount);
                $quoteItem->setBasePriceInclTax($quoteItem->getBasePrice() + ($taxAmount / $quoteItem->getQty()));
                $quoteItem->setPriceInclTax($quoteItem->getPrice() + ($taxAmount / $quoteItem->getQty()));

                $rate = 0;
                if ($orderLine->getTaxes()->count() === 1) {
                    // Try to retrieve an unique rate from Mirakl OR11 payload if present
                    $rate = $orderLine->getTaxes()->first()->getRate();
                }
                if (!$rate) {
                    // If no rate is found, calculate it
                    $rate = round(($taxAmount / $quoteItem->getRowTotal()) * 100);
                }
                $quoteItem->setTaxPercent($rate);
            } else {
                $quoteItem->setTaxAmount(0);
                $quoteItem->setBaseTaxAmount(0);
                $quoteItem->setBaseRowTotalInclTax($quoteItem->getBaseRowTotal());
                $quoteItem->setRowTotalInclTax($quoteItem->getRowTotal());
                $quoteItem->setBasePriceInclTax($quoteItem->getBasePrice());
                $quoteItem->setPriceInclTax($quoteItem->getPrice());
                $quoteItem->setTaxPercent(0);
            }

            /** @var QuoteItemResource $quoteItemResource */
            $quoteItemResource = $this->quoteItemResourceFactory->create();
            $quoteItemResource->save($quoteItem);
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'An error occurred for product "%1" (%2): %3', $product->getName(), $sku, $e->getMessage()
            ));
        }

        return $quoteItem;
    }
}
