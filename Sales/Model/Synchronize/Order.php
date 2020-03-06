<?php
namespace MiraklSeller\Sales\Model\Synchronize;

use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order as OrderModel;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\Config;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Model\Create\Invoice as InvoiceCreator;
use MiraklSeller\Sales\Model\Create\Shipment as ShipmentCreator;
use MiraklSeller\Sales\Model\Synchronize\Refunds as SynchronizeRefunds;

class Order
{
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var InvoiceCreator
     */
    protected $invoiceCreator;

    /**
     * @var ShipmentCreator
     */
    protected $shipmentCreator;

    /**
     * @var SynchronizeRefunds
     */
    protected $synchronizeRefunds;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param   OrderManagementInterface    $orderManagement
     * @param   InvoiceCreator              $invoiceCreator
     * @param   ShipmentCreator             $shipmentCreator
     * @param   SynchronizeRefunds          $synchronizeRefunds
     * @param   Config                      $config
     * @param   OrderHelper                 $orderHelper
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        InvoiceCreator $invoiceCreator,
        ShipmentCreator $shipmentCreator,
        SynchronizeRefunds $synchronizeRefunds,
        Config $config,
        OrderHelper $orderHelper
    ) {
        $this->orderManagement    = $orderManagement;
        $this->invoiceCreator     = $invoiceCreator;
        $this->shipmentCreator    = $shipmentCreator;
        $this->synchronizeRefunds = $synchronizeRefunds;
        $this->config             = $config;
        $this->orderHelper        = $orderHelper;
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   OrderModel  $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize($magentoOrder, $miraklOrder)
    {
        $updated = false; // Flag to mark Magento order as updated or not

        $magentoState = $magentoOrder->getState();
        $miraklState  = $miraklOrder->getStatus()->getState();
        $hasInvoice   = $magentoOrder->getInvoiceCollection()->count();
        $canInvoice   = !$hasInvoice && $this->config->isAutoCreateInvoice();
        $hasShipment  = $magentoOrder->getShipmentsCollection()->count();
        $canShip      = !$hasShipment && $this->config->isAutoCreateShipment();

        // Cancel Magento order if Mirakl order is canceled
        if ($miraklState == OrderState::CANCELED && !$magentoOrder->isCanceled()) {
            $updated = true;
            $this->orderManagement->cancel($magentoOrder->getId());
        }

        // Block Magento order if Mirakl order has been refused
        if ($miraklState == OrderState::REFUSED && $magentoState != OrderModel::STATE_HOLDED) {
            $updated = true;
            $this->orderManagement->hold($magentoOrder->getId());
        }

        // Create Magento invoice if Mirakl order has been invoiced
        if ($canInvoice && $this->orderHelper->isMiraklOrderInvoiced($miraklOrder)) {
            $updated = true;
            $this->invoiceCreator->create($magentoOrder);
        }

        // Create Magento shipment if Mirakl order has been shipped
        if ($canShip && $this->orderHelper->isMiraklOrderShipped($miraklOrder)) {
            $updated = true;
            $this->shipmentCreator->create($magentoOrder, $miraklOrder);
        }

        // Synchronize Mirakl refunds with Magento order
        if ($this->config->isAutoCreateRefunds()) {
            $this->synchronizeRefunds->synchronize($magentoOrder, $miraklOrder);
        }

        return $updated;
    }
}