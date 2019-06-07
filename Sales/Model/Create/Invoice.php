<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;

class Invoice
{
    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @param   TransactionFactory  $transactionFactory
     */
    public function __construct(TransactionFactory $transactionFactory)
    {
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param   Order  $order
     * @param   array  $qtys
     * @return  InvoiceModel
     * @throws  \Exception
     */
    public function create(Order $order, array $qtys = [])
    {
        if (!$order->canInvoice()) {
            throw new \Exception('Cannot do invoice for the order.');
        }

        $invoice = $order->prepareInvoice($qtys);
        $invoice->addComment('Invoice automatically created by the Mirakl Seller Connector.');
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $invoice;
    }
}