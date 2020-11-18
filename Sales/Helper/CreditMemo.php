<?php
namespace MiraklSeller\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;

class CreditMemo extends AbstractHelper
{
    /**
     * @var CreditMemoCollectionFactory
     */
    protected $creditMemoCollectionFactory;

    /**
     * @param   Context                     $context
     * @param   CreditMemoCollectionFactory $creditMemoCollectionFactory
     */
    public function __construct(
        Context $context,
        CreditMemoCollectionFactory $creditMemoCollectionFactory)
    {
        parent::__construct($context);

        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
    }

    /**
     * @param   int $miraklRefundId
     * @return  OrderModel\Creditmemo
     */
    public function getCreditMemoByMiraklRefundId($miraklRefundId)
    {
        /** @var OrderModel\Creditmemo $creditMemo */
        $creditMemo = $this->creditMemoCollectionFactory->create()
            ->addFieldToFilter('mirakl_refund_id', $miraklRefundId)
            ->getFirstItem();

        return $creditMemo;
    }
}