<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote\Address\Rate as AddressRate;
use Magento\Quote\Model\Quote\Address\RateFactory as AddressRateFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Model\Create\QuoteItem as QuoteItemCreator;
use MiraklSeller\Sales\Model\Mapper\MapperInterface;

class Quote
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AddressRateFactory
     */
    protected $addressRateFactory;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteResourceFactory
     */
    protected $quoteResourceFactory;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var QuoteItemCreator
     */
    protected $quoteItemCreator;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var MapperInterface
     */
    protected $addressMapper;

    /**
     * @var string
     */
    protected $defaultCustomerEmail;

    /**
     * @param   StoreManagerInterface   $storeManager
     * @param   AddressRateFactory      $addressRateFactory
     * @param   QuoteFactory            $quoteFactory
     * @param   QuoteResourceFactory    $quoteResourceFactory
     * @param   CurrencyFactory         $currencyFactory
     * @param   QuoteItemCreator        $quoteItemCreator
     * @param   OrderHelper             $orderHelper
     * @param   MapperInterface         $addressMapper
     * @param   string                  $defaultCustomerEmail
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AddressRateFactory $addressRateFactory,
        QuoteFactory $quoteFactory,
        QuoteResourceFactory $quoteResourceFactory,
        CurrencyFactory $currencyFactory,
        QuoteItemCreator $quoteItemCreator,
        OrderHelper $orderHelper,
        MapperInterface $addressMapper,
        $defaultCustomerEmail = 'guest@do-not-use.com'
    ) {
        $this->storeManager         = $storeManager;
        $this->addressRateFactory   = $addressRateFactory;
        $this->quoteFactory         = $quoteFactory;
        $this->quoteResourceFactory = $quoteResourceFactory;
        $this->currencyFactory      = $currencyFactory;
        $this->quoteItemCreator     = $quoteItemCreator;
        $this->orderHelper          = $orderHelper;
        $this->addressMapper        = $addressMapper;
        $this->defaultCustomerEmail = $defaultCustomerEmail;
    }

    /**
     * @param  ShopOrder $miraklOrder
     * @param  mixed     $store
     * @return QuoteModel
     */
    public function create(ShopOrder $miraklOrder, $store = null)
    {
        $store = $this->storeManager->getStore($store);
        if ($store->getId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $store = $this->storeManager->getDefaultStoreView();
        }

        /** @var \Magento\Directory\Model\Currency $quoteCurrency */
        $quoteCurrency = $this->currencyFactory->create();
        $quoteCurrency->load($miraklOrder->getCurrencyIsoCode());

        /** @var QuoteModel $quote */
        $quote = $this->quoteFactory->create();
        $quote->setStoreId($store->getId())
            ->setForcedCurrency($quoteCurrency)
            ->setIsSuperMode(true)
            ->setFromMiraklOrder(true);

        /** @var QuoteResource $quoteResource */
        $quoteResource = $this->quoteResourceFactory->create();
        $quoteResource->save($quote);

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if (in_array($orderLine->getStatus()->getState(), [OrderState::REFUSED, OrderState::CANCELED])) {
                continue; // Do not use refused or canceled order lines
            }

            // Create quote item
            $this->quoteItemCreator->create($quote, $orderLine);
        }

        if (empty($quote->getAllVisibleItems())) {
            throw new LocalizedException(__('Could not find any valid products for order creation.'));
        }

        $taxAmount = $this->orderHelper->getMiraklOrderTaxAmount($miraklOrder);
        $shippingTaxAmount = $this->orderHelper->getMiraklOrderShippingTaxAmount($miraklOrder);
        $grandTotal = $miraklOrder->getTotalPrice() + $taxAmount + $shippingTaxAmount;

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
            ->setCustomerEmail($this->getCustomerEmail($miraklOrder))
            ->setCustomerId(null)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);

        $quote->setTotalsCollectedFlag(true);

        $quote->getPayment()->setQuote($quote)->importData(['method' => 'mirakl']);

        $quote->setBaseCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setQuoteCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setBaseSubtotal($miraklOrder->getPrice())
            ->setSubtotal($miraklOrder->getPrice())
            ->setBaseGrandTotal($grandTotal)
            ->setGrandTotal($grandTotal);

        $quoteResource->save($quote);

        $addressRate = $this->getAddressRate($miraklOrder, $quote);

        $quote->getShippingAddress()
            ->setShippingMethod($addressRate->getCode())
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

        return $quote;
    }

    /**
     * This method can be overriden to customize the address rate used to create the quote
     *
     * @param   ShopOrder   $miraklOrder
     * @param   QuoteModel  $quote
     * @return  AddressRate
     */
    public function getAddressRate(ShopOrder $miraklOrder, QuoteModel $quote)
    {
        /** @var AddressRate $addressRate */
        $addressRate = $this->addressRateFactory->create();
        $addressRate->setAddress($quote->getShippingAddress())
            ->setAddressId($quote->getShippingAddress()->getId())
            ->setCode('flatrate_flatrate')
            ->setMethod('flatrate')
            ->setCarrier('flatrate')
            ->setCarrierTitle($miraklOrder->getShipping()->getType()->getLabel())
            ->setMethodTitle($miraklOrder->getShipping()->getType()->getLabel());

        return $addressRate;
    }

    /**
     * This method can be overriden to customize the customer email used to create the quote
     *
     * @param   ShopOrder   $miraklOrder
     * @return  string
     */
    public function getCustomerEmail(ShopOrder $miraklOrder)
    {
        return $this->defaultCustomerEmail;
    }
}
