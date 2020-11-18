<?php
namespace MiraklSeller\Sales\Model\Synchronize;

use Magento\Sales\Model\Order\Creditmemo as CreditMemoModel;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\CreditmemoFactory as CreditMemoResourceFactory;
use Mirakl\MMP\Common\Domain\Order\Refund;
use Mirakl\MMP\Common\Domain\Order\Refund\RefundState;

class CreditMemo
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo
     */
    protected $creditMemoResource;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $orderResource;

    /**
     * @param   CreditMemoResourceFactory   $creditMemoResourceFactory
     * @param   OrderResourceFactory        $orderResourceFactory
     */
    public function __construct(
        CreditMemoResourceFactory $creditMemoResourceFactory,
        OrderResourceFactory $orderResourceFactory
    ) {
        $this->creditMemoResource = $creditMemoResourceFactory->create();
        $this->orderResource = $orderResourceFactory->create();
    }

    /**
     * Returns true if Magento credit memo has been updated or false if not
     *
     * @param   CreditMemoModel $creditMemo
     * @param   Refund          $miraklRefund
     * @return  bool
     */
    public function synchronize(CreditMemoModel $creditMemo, Refund $miraklRefund)
    {
        $updated = false; // Flag to mark Magento credit memo as updated or not

        if ($creditMemo->getState() == CreditMemoModel::STATE_OPEN && $miraklRefund->getState() == RefundState::REFUNDED) {
            $creditMemo->setState(CreditMemoModel::STATE_REFUNDED);
            $this->creditMemoResource->save($creditMemo);

            // Save refunded amount
            $magentoOrder = $creditMemo->getOrder();
            $magentoOrder->setBaseTotalRefunded($magentoOrder->getBaseTotalRefunded() + $creditMemo->getBaseGrandTotal());
            $magentoOrder->setTotalRefunded($magentoOrder->getTotalRefunded() + $creditMemo->getGrandTotal());
            $this->orderResource->save($magentoOrder);

            $updated = true;
        }

        return $updated;
    }
}