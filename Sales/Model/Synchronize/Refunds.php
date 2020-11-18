<?php
namespace MiraklSeller\Sales\Model\Synchronize;

use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\CreditMemo as CreditMemoHelper;
use MiraklSeller\Sales\Model\Create\Refund as RefundCreator;
use MiraklSeller\Sales\Model\Synchronize\CreditMemo as CreditMemoSynchronizer;

class Refunds
{
    /**
     * @var RefundCreator
     */
    protected $refundCreator;

    /**
     * @var CreditMemoSynchronizer
     */
    protected $creditMemoSynchronizer;

    /**
     * @var CreditMemoHelper
     */
    protected $creditMemoHelper;

    /**
     * @param   RefundCreator           $refundCreator
     * @param   CreditMemoSynchronizer  $creditMemoSynchronizer
     * @param   CreditMemoHelper        $creditMemoHelper
     */
    public function __construct(
        RefundCreator $refundCreator,
        CreditMemoSynchronizer  $creditMemoSynchronizer,
        CreditMemoHelper $creditMemoHelper
    ) {
        $this->refundCreator = $refundCreator;
        $this->creditMemoSynchronizer = $creditMemoSynchronizer;
        $this->creditMemoHelper = $creditMemoHelper;
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   Order       $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize(Order $magentoOrder, ShopOrder $miraklOrder)
    {
        $updated = false; // Flag to mark Magento order as updated or not

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Refund $refund */
            foreach ($orderLine->getRefunds() as $refund) {
                $existingCreditMemo = $this->creditMemoHelper->getCreditMemoByMiraklRefundId($refund->getId());
                if ($existingCreditMemo->getId()) {
                    if ($this->creditMemoSynchronizer->synchronize($existingCreditMemo, $refund)) {
                        $updated = true;
                    }
                } elseif ($magentoOrder->canCreditmemo() && null !== $this->refundCreator->create($magentoOrder, $orderLine, $refund)) {
                    $updated = true;
                }
            }
        }

        return $updated;
    }
}
