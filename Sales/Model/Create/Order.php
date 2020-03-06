<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote\Address\RateFactory as AddressRateFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address\RateFactory as AddressRateResourceFactory;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Magento\Sales\Model\Order\Tax\ItemFactory as OrderTaxItemFactory;
use Magento\Sales\Model\Order\Tax;
use Magento\Sales\Model\ResourceModel\Order\TaxFactory as OrderTaxResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\ItemFactory as OrderTaxItemResourceFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Sales\Order\TaxFactory as OrderTaxFactory;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\Data as SalesHelper;
use MiraklSeller\Sales\Model\InventorySales\SkipQtyCheckFlag;
use MiraklSeller\Sales\Model\Mapper\MapperInterface;

class Order
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var MapperInterface
     */
    protected $addressMapper;

    /**
     * @var SalesHelper
     */
    protected $salesHelper;

    /**
     * @var SkipQtyCheckFlag
     */
    protected $skipQtyCheckFlag;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $customerEmail;

    /**
     * @param   StoreManagerInterface       $storeManager
     * @param   ProductRepositoryInterface  $productRepository
     * @param   CartManagementInterface     $quoteManagement
     * @param   MapperInterface             $addressMapper
     * @param   SalesHelper                 $salesHelper
     * @param   SkipQtyCheckFlag            $skipQtyCheckFlag
     * @param   ObjectManagerInterface      $objectManager
     * @param   string                      $customerEmail
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CartManagementInterface $quoteManagement,
        MapperInterface $addressMapper,
        SalesHelper $salesHelper,
        SkipQtyCheckFlag $skipQtyCheckFlag,
        ObjectManagerInterface $objectManager,
        $customerEmail = 'guest@do-not-use.com'
    ) {
        $this->storeManager      = $storeManager;
        $this->productRepository = $productRepository;
        $this->quoteManagement   = $quoteManagement;
        $this->addressMapper     = $addressMapper;
        $this->salesHelper       = $salesHelper;
        $this->skipQtyCheckFlag  = $skipQtyCheckFlag;
        $this->objectManager     = $objectManager;
        $this->customerEmail     = $customerEmail;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @param   mixed       $store
     * @return  \Magento\Sales\Model\Order
     * @throws  LocalizedException
     * @throws  NotFoundException
     */
    public function create(ShopOrder $miraklOrder, $store = null)
    {
        $store = $this->storeManager->getStore($store);
        if ($store->getId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $store = $this->storeManager->getDefaultStoreView();
        }

        $quoteCurrency = $this->objectManager->get(CurrencyFactory::class)
            ->create()
            ->load($miraklOrder->getCurrencyIsoCode());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->get(QuoteFactory::class)->create();
        $quote->setStoreId($store->getId())
            ->setForcedCurrency($quoteCurrency)
            ->setIsSuperMode(true)
            ->setFromMiraklOrder(true);

        /** @var \Magento\Quote\Model\ResourceModel\Quote $quoteResource */
        $quoteResource = $this->objectManager->get(QuoteResourceFactory::class)->create();
        $quoteResource->save($quote);

        $oldSkipQtyCheckFlag = $this->skipQtyCheckFlag->getQtySkipQtyCheck();
        $this->skipQtyCheckFlag->setSkipQtyCheck(true);

        $quoteTaxes = $quoteItemsTaxes = [];
        $taxAmount = 0;
        $shippingTaxAmount = 0;

        /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource */
        $quoteItemResource = $this->objectManager->get(QuoteItemResourceFactory::class)->create();

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if ($orderLine->getStatus()->getState() == 'REFUSED') {
                continue; // Ignore refused items on Mirakl
            }

            $sku = $orderLine->getOffer()->getSku();

            try {
                // Try to find attached product in Magento
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->productRepository->get($sku);
            } catch (NoSuchEntityException $e) {
                throw new NotFoundException(__('Product "%1" could not be found in Magento catalog.', $sku));
            }

            // Force the product status to 'enabled' because price is set to 0 if status is 'disabled'
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setOrigData('status', Status::STATUS_ENABLED);

            // Force the salable flag too
            $product->setData('salable', true);

            $buyInfo = ['qty' => $orderLine->getQuantity()];
            $product->setPriceCalculation(false);
            $product->setData('price', $orderLine->getOffer()->getPrice());
            $product->setData('final_price', $orderLine->getOffer()->getPrice());
            $product->unsetData('tax_class_id');

            try {
                $quoteItem = $quote->addProduct($product, new DataObject($buyInfo));
                $quoteItemResource->save($quoteItem);

                /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
                foreach ($orderLine->getTaxes() as $tax) {
                    $taxAmount += $tax->getAmount();
                    if (!isset($quoteTaxes[$tax->getCode()])) {
                        $quoteTaxes[$tax->getCode()] = 0;
                    }
                    $quoteTaxes[$tax->getCode()] += $tax->getAmount();

                    if (!isset($quoteItemsTaxes['product'][$quoteItem->getId()][$tax->getCode()])) {
                        $quoteItemsTaxes['product'][$quoteItem->getId()][$tax->getCode()] = 0;
                    }
                    $quoteItemsTaxes['product'][$quoteItem->getId()][$tax->getCode()] += $tax->getAmount();
                }

                foreach ($orderLine->getShippingTaxes() as $tax) {
                    $shippingTaxAmount += $tax->getAmount();
                    if (!isset($quoteTaxes[$tax->getCode()])) {
                        $quoteTaxes[$tax->getCode()] = 0;
                    }
                    $quoteTaxes[$tax->getCode()] += $tax->getAmount();

                    if (!isset($quoteItemsTaxes['shipping'][$quoteItem->getId()][$tax->getCode()])) {
                        $quoteItemsTaxes['shipping'][$quoteItem->getId()][$tax->getCode()] = 0;
                    }
                    $quoteItemsTaxes['shipping'][$quoteItem->getId()][$tax->getCode()] += $tax->getAmount();
                }
            } catch (\Exception $e) {
                throw new LocalizedException(__(
                    'An error occurred for product "%1" (%2): %3', $product->getName(), $sku, $e->getMessage()
                ));
            }
        }

        if (empty($quote->getAllVisibleItems())) {
            throw new LocalizedException(__('Could not find any valid products for order creation.'));
        }

        $totalTaxAmount = $taxAmount + $shippingTaxAmount;
        $grandTotal = $miraklOrder->getTotalPrice() + $totalTaxAmount;

        $customer = $miraklOrder->getCustomer();
        $locale = $customer->getLocale();

        $billingAddress = $this->addressMapper->map($customer->getBillingAddress()->toArray(), $locale);
        $quote->getBillingAddress()
            ->addData($billingAddress)
            ->setShouldIgnoreValidation(true);

        $shippingAddress = $this->addressMapper->map($customer->getShippingAddress()->toArray(), $locale);
        $quote->getShippingAddress()
            ->addData($shippingAddress)
            ->setCollectShippingRates(true)
            ->setShouldIgnoreValidation(true);

        $quote->setCheckoutMethod('guest')
            ->setCustomerEmail($this->customerEmail)
            ->setCustomerId(null)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);

        $quote->getPayment()->setQuote($quote)->importData(['method' => 'mirakl']);
        $quote->setBaseCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setQuoteCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setBaseSubtotal($miraklOrder->getPrice())
            ->setSubtotal($miraklOrder->getPrice())
            ->setBaseGrandTotal($grandTotal)
            ->setGrandTotal($grandTotal);

        $quoteResource->save($quote);

        /** @var \Magento\Quote\Model\Quote\Address\Rate $addressRate */
        $addressRate = $this->objectManager->get(AddressRateFactory::class)->create();
        $addressRate->setAddress($quote->getShippingAddress())
            ->setAddressId($quote->getShippingAddress()->getId())
            ->setCode('flatrate_flatrate')
            ->setMethod('flatrate')
            ->setCarrier('flatrate')
            ->setCarrierTitle($miraklOrder->getShipping()->getType()->getLabel())
            ->setMethodTitle($miraklOrder->getShipping()->getType()->getLabel());

        $this->objectManager->get(AddressRateResourceFactory::class)
            ->create()
            ->save($addressRate);

        $quote->getShippingAddress()
            ->setShippingMethod('flatrate_flatrate')
            ->setShippingDescription($miraklOrder->getShipping()->getType()->getLabel())
            ->setBaseShippingAmount($miraklOrder->getShipping()->getPrice())
            ->setShippingAmount($miraklOrder->getShipping()->getPrice())
            ->setBaseTaxAmount($taxAmount + $shippingTaxAmount)
            ->setTaxAmount($taxAmount + $shippingTaxAmount)
            ->setBaseShippingTaxAmount($shippingTaxAmount)
            ->setShippingTaxAmount($shippingTaxAmount)
            ->setBaseShippingInclTax($miraklOrder->getShipping()->getPrice() + $shippingTaxAmount)
            ->setShippingInclTax($miraklOrder->getShipping()->getPrice() + $shippingTaxAmount)
            ->setBaseSubtotal($miraklOrder->getPrice())
            ->setSubtotal($miraklOrder->getPrice())
            ->setBaseSubtotalTotalInclTax($miraklOrder->getPrice() + $taxAmount)
            ->setSubtotalInclTax($miraklOrder->getPrice() + $taxAmount)
            ->setBaseGrandTotal($grandTotal)
            ->setGrandTotal($grandTotal)
            ->addShippingRate($addressRate);

        $quoteResource->save($quote);

        // Save taxes amount on each quote item before placing the order
        foreach ($quote->getAllVisibleItems() as $item) {
            if (!empty($quoteItemsTaxes['product'][$item->getId()])) {
                $itemTaxAmount = array_sum($quoteItemsTaxes['product'][$item->getId()]);
                $item->setTaxAmount($itemTaxAmount);
                $item->setBaseTaxAmount($itemTaxAmount);
                $item->setBaseRowTotalInclTax($item->getBaseRowTotalInclTax() + $itemTaxAmount);
                $item->setRowTotalInclTax($item->getRowTotalInclTax() + $itemTaxAmount);
                $item->setBasePriceInclTax($item->getBasePrice() + ($itemTaxAmount / $item->getQty()));
                $item->setPriceInclTax($item->getPriceInclTax() + ($itemTaxAmount / $item->getQty()));
                $quoteItemResource->save($item);
            }
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->quoteManagement->submit($quote);

        $order->setTaxAmount($totalTaxAmount);
        $order->setShippingTaxAmount($shippingTaxAmount);
        $this->objectManager->get(OrderResourceFactory::class)
            ->create()
            ->save($order);

        // Save order taxes by code
        foreach ($quoteTaxes as $code => $amount) {
            $data = [
                'order_id'         => $order->getId(),
                'code'             => $code,
                'title'            => $code,
                'hidden'           => 0,
                'percent'          => 0,
                'priority'         => 0,
                'position'         => 0,
                'process'          => 0,
                'amount'           => $amount,
                'base_amount'      => $amount,
                'base_real_amount' => $amount,
            ];

            /** @var Tax $orderTax */
            $orderTax = $this->objectManager->get(OrderTaxFactory::class)->create();
            $orderTax->setData($data);
            $this->objectManager->get(OrderTaxResourceFactory::class)
                ->create()
                ->save($orderTax);

            // Save order item taxes by code
            foreach ($quoteItemsTaxes as $taxableItemType => $quoteItemTaxDetails) {
                foreach ($quoteItemTaxDetails as $quoteItemId => $taxDetails) {
                    if (null === $orderItem = $order->getItemByQuoteItemId($quoteItemId)) {
                        continue;
                    }

                    foreach ($taxDetails as $code => $amount) {
                        if ($code !== $orderTax->getCode()) {
                            continue;
                        }

                        $data = [
                            'item_id'            => $taxableItemType == 'product' ? $orderItem->getId() : null,
                            'tax_id'             => $orderTax->getId(),
                            'tax_percent'        => 0,
                            'associated_item_id' => null,
                            'amount'             => $amount,
                            'base_amount'        => $amount,
                            'real_amount'        => $amount,
                            'real_base_amount'   => $amount,
                            'taxable_item_type'  => $taxableItemType,
                        ];

                        /** @var \Magento\Sales\Model\Order\Tax\Item $orderTaxItem */
                        $orderTaxItem = $this->objectManager->get(OrderTaxItemFactory::class)->create();
                        $orderTaxItem->setData($data);
                        $this->objectManager->get(OrderTaxItemResourceFactory::class)
                            ->create()
                            ->save($orderTaxItem);
                    }
                }
            }
        }

        $this->skipQtyCheckFlag->setSkipQtyCheck($oldSkipQtyCheckFlag);

        return $order;
    }
}
