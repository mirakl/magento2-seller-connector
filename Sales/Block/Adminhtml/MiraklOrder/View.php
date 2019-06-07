<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder;

use Magento\Backend\Block\Template;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class View extends Template
{
    /**
     * @var CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var MiraklOrderLoader
     */
    protected $miraklOrderLoader;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * @param   Template\Context    $context
     * @param   CurrencyInterface   $localeCurrency
     * @param   ConnectionLoader    $connectionLoader
     * @param   MiraklOrderLoader   $miraklOrderLoader
     * @param   OrderHelper         $orderHelper
     * @param   ConnectionHelper    $connectionHelper
     * @param   array               $data
     */
    public function __construct(
        Template\Context $context,
        CurrencyInterface $localeCurrency,
        ConnectionLoader $connectionLoader,
        MiraklOrderLoader $miraklOrderLoader,
        OrderHelper $orderHelper,
        ConnectionHelper $connectionHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->localeCurrency    = $localeCurrency;
        $this->connectionLoader  = $connectionLoader;
        $this->miraklOrderLoader = $miraklOrderLoader;
        $this->orderHelper       = $orderHelper;
        $this->connectionHelper  = $connectionHelper;
    }

    /**
     * @param   float   $price
     * @param   string  $currency
     * @return  string
     */
    public function formatPrice($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  string
     */
    public function getConnectionUrl()
    {
        return $this->getUrl('mirakl_seller/connection/edit', ['id' => $this->getConnection()->getId()]);
    }

    /**
     * @param   string  $code
     * @return  string
     */
    public function getCountry($code)
    {
        return $this->orderHelper->getCountryByCode($code);
    }

    /**
     * @return  float
     */
    public function getGrandTotal()
    {
        return $this->getMiraklOrder()->getTotalPrice() + $this->getTaxAmount(true);
    }

    /**
     * @return  string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('*/*/cancel', [
            'connection_id' => $this->getConnection()->getId(),
            'order_id'      => $this->getMiraklOrder()->getId(),
        ]);
    }

    /**
     * @return  string
     */
    public function getImportUrl()
    {
        return $this->getUrl('*/*/import', [
            'connection_id' => $this->getConnection()->getId(),
            'order_id'      => $this->getMiraklOrder()->getId(),
        ]);
    }

    /**
     * @return  Order|null
     */
    public function getMagentoOrder()
    {
        return $this->orderHelper->getOrderByMiraklOrderId($this->getMiraklOrder()->getId());
    }

    /**
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     */
    public function getMiraklOrder()
    {
        return $this->miraklOrderLoader->getCurrentMiraklOrder($this->getConnection());
    }

    /**
     * @return  string
     */
    public function getMiraklOrderState()
    {
        return $this->getMiraklOrder()->getStatus()->getState();
    }

    /**
     * @return  string
     */
    public function getMiraklOrderUrl()
    {
        return $this->connectionHelper->getMiraklOrderUrl($this->getConnection(), $this->getMiraklOrder());
    }

    /**
     * The payment duration (i.e. the delay after which the order is supposed to be paid), in days.
     * Only applicable for PAY_ON_DUE_DATE orders.
     * Note that this field has currently no impact on the order workflow, it is just there for information purposes.
     *
     * @return  string|null
     */
    public function getPaymentDuration()
    {
        return $this->getMiraklOrder()->getPaymentDuration() ?: null;
    }

    /**
     * @return  float
     */
    public function getShippingTaxAmount()
    {
        return $this->orderHelper->getMiraklOrderShippingTaxAmount($this->getMiraklOrder());
    }

    /**
     * @param   bool    $withShipping
     * @return  float
     */
    public function getTaxAmount($withShipping = false)
    {
        return $this->orderHelper->getMiraklOrderTaxAmount($this->getMiraklOrder(), $withShipping);
    }

    /**
     * @return  bool
     */
    protected function isOrderInShipping()
    {
        return $this->getMiraklOrderState() === OrderState::SHIPPING;
    }

    /**
     * @return  bool
     */
    protected function isOrderWaitingAcceptance()
    {
        return $this->getMiraklOrderState() === OrderState::WAITING_ACCEPTANCE;
    }

    /**
     * @return  bool
     */
    protected function isOrderWaitingDebitPayment()
    {
        return $this->getMiraklOrderState() === OrderState::WAITING_DEBIT_PAYMENT;
    }
}