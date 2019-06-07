<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo as CreditMemo;
use Magento\Sales\Model\Order\CreditmemoFactory as CreditMemoFactory;
use Mirakl\MMP\Common\Domain\Order\Refund as MiraklRefund;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use MiraklSeller\Sales\Helper\CreditMemo as CreditMemoHelper;

class Refund
{
    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var CreditMemoHelper
     */
    protected $creditMemoHelper;

    /**
     * @var CreditMemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @param   TransactionFactory  $transactionFactory
     * @param   CreditMemoHelper    $creditMemoHelper
     * @param   CreditMemoFactory   $creditMemoFactory
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        CreditMemoHelper $creditMemoHelper,
        CreditMemoFactory $creditMemoFactory
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->creditMemoHelper   = $creditMemoHelper;
        $this->creditMemoFactory  = $creditMemoFactory;
    }

    /**
     * @param   Order           $magentoOrder
     * @param   ShopOrderLine   $miraklOrderLine
     * @param   MiraklRefund    $refund
     * @return  Order\Creditmemo|null
     * @throws  \Exception
     */
    public function create(Order $magentoOrder, ShopOrderLine $miraklOrderLine, MiraklRefund $refund)
    {
        if (!$magentoOrder->canCreditmemo()) {
            throw new \Exception('Cannot create credit memo for the order.');
        }

        $existingCreditMemo = $this->creditMemoHelper->getCreditMemoByMiraklRefundId($refund->getId());
        if ($existingCreditMemo->getId()) {
            return null;
        }

        $orderItem = $this->getOrderItemBySku($magentoOrder, $miraklOrderLine->getOffer()->getSku());

        if (!$orderItem) {
            return null;
        }

        $setZeroItemQty = false;
        if (!$refund->getQuantity() && $refund->getAmount()) {
            // Set quantity to 1 temporarily to allow credit memo item creation
            $refund->setQuantity(1);
            $setZeroItemQty = true;
        }

        $creditMemoData = [
            'qtys' => [$orderItem->getId() => $refund->getQuantity()],
        ];

        $creditMemo = $this->creditMemoFactory->createByOrder($magentoOrder, $creditMemoData);

        $creditMemoItem = null;
        /** @var CreditmemoItemInterface $creditMemoItem */
        foreach ($creditMemo->getItems() as $k => $item) {
            if ($item->getSku() === $miraklOrderLine->getOffer()->getSku()) {
                $creditMemoItem = $item; // Retrieve credit memo item associated to Mirakl offer sku
            }
        }

        if (!$creditMemoItem) {
            return null;
        }

        $itemTax = 0;
        foreach ($refund->getTaxes() as $tax) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            $itemTax += $tax->getAmount();
        }

        $creditMemoItem->setTaxAmount($itemTax);

        if ($refund->getQuantity()) {
            $creditMemoItem->setBasePrice($refund->getAmount() / $refund->getQuantity());
            $creditMemoItem->setPrice($refund->getAmount() / $refund->getQuantity());
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + ($itemTax / $refund->getQuantity()));
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + ($itemTax / $refund->getQuantity()));
        } else {
            $creditMemoItem->setBasePrice($refund->getAmount());
            $creditMemoItem->setPrice($refund->getAmount());
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + $itemTax);
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + $itemTax);
        }

        $creditMemoItem->setBaseRowTotal($refund->getAmount());
        $creditMemoItem->setRowTotal($refund->getAmount());
        $creditMemoItem->setBaseRowTotalInclTax($refund->getAmount() + $itemTax);
        $creditMemoItem->setRowTotalInclTax($refund->getAmount() + $itemTax);

        if ($setZeroItemQty) {
            $creditMemoItem->setQty(0);
        }

        $shippingTax = 0;
        foreach ($refund->getShippingTaxes() as $tax) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            $shippingTax += $tax->getAmount();
        }

        // Shipping tax amount
        $creditMemo->setBaseShippingTaxAmount($shippingTax);
        $creditMemo->setShippingTaxAmount($shippingTax);

        // Shipping amount excluding tax
        $creditMemo->setBaseShippingAmount($refund->getShippingAmount());
        $creditMemo->setShippingAmount($refund->getShippingAmount());

        // Shipping amount including tax
        $creditMemo->setBaseShippingInclTax($refund->getShippingAmount() + $shippingTax);
        $creditMemo->setShippingInclTax($refund->getShippingAmount() + $shippingTax);

        // Subtotal amount excluding tax
        $creditMemo->setBaseSubtotal($creditMemoItem->getBaseRowTotal());
        $creditMemo->setSubtotal($creditMemoItem->getRowTotal());

        // Subtotal amount including tax
        $creditMemo->setBaseSubtotalInclTax($creditMemoItem->getBaseRowTotalInclTax());
        $creditMemo->setSubtotalInclTax($creditMemoItem->getRowTotalInclTax());

        // Grand total including tax
        $creditMemo->setBaseGrandTotal($creditMemo->getBaseSubtotalInclTax() + $creditMemo->getBaseShippingInclTax());
        $creditMemo->setGrandTotal($creditMemo->getSubtotalInclTax() + $creditMemo->getShippingInclTax());

        // Total tax amount
        $creditMemo->setBaseTaxAmount($itemTax + $shippingTax);
        $creditMemo->setTaxAmount($itemTax + $shippingTax);

        // Credit memo state
        if ($refund->getState() === MiraklRefund\RefundState::REFUNDED) {
            $creditMemo->setState(CreditMemo::STATE_REFUNDED);

            // Save refunded amount only if refund had been paid
            $magentoOrder->setBaseTotalRefunded($magentoOrder->getBaseTotalRefunded() + $creditMemo->getBaseGrandTotal());
            $magentoOrder->setTotalRefunded($magentoOrder->getTotalRefunded() + $creditMemo->getGrandTotal());
        } else {
            $creditMemo->setState(CreditMemo::STATE_OPEN);
        }

        // Save Mirakl refund id on the credit memo to mark it as imported
        $creditMemo->setMiraklRefundId($refund->getId());
        $creditMemo->setMiraklRefundTaxes(json_encode($refund->getTaxes()->toArray()));
        $creditMemo->setMiraklRefundShippingTaxes(json_encode($refund->getShippingTaxes()->toArray()));

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($creditMemo)
            ->addObject($magentoOrder)
            ->save();

        return $creditMemo;
    }

    /**
     * @param   Order   $magentoOrder
     * @param   string  $sku
     * @return  Order\Item|null
     */
    private function getOrderItemBySku(Order $magentoOrder, $sku)
    {
        /** @var Order\Item $orderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getSku() === $sku) {
                return $orderItem;
            }
        }

        return null;
    }
}