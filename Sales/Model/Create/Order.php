<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\CartManagementInterface;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Model\Create\Quote as QuoteCreator;
use MiraklSeller\Sales\Model\Create\OrderTaxes as OrderTaxesCreator;
use MiraklSeller\Sales\Model\InventorySales\SkipQtyCheckFlag;

class Order
{
    /**
     * @var CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var QuoteCreator
     */
    protected $quoteCreator;

    /**
     * @var OrderTaxesCreator
     */
    protected $orderTaxesCreator;

    /**
     * @var SkipQtyCheckFlag
     */
    protected $skipQtyCheckFlag;

    /**
     * @param CartManagementInterface $quoteManagement
     * @param QuoteCreator            $quoteCreator
     * @param OrderTaxesCreator       $orderTaxesCreator
     * @param SkipQtyCheckFlag        $skipQtyCheckFlag
     */
    public function __construct(
        CartManagementInterface $quoteManagement,
        QuoteCreator $quoteCreator,
        OrderTaxesCreator $orderTaxesCreator,
        SkipQtyCheckFlag $skipQtyCheckFlag
    ) {
        $this->quoteManagement   = $quoteManagement;
        $this->quoteCreator      = $quoteCreator;
        $this->orderTaxesCreator = $orderTaxesCreator;
        $this->skipQtyCheckFlag  = $skipQtyCheckFlag;
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
        $oldSkipQtyCheckFlag = $this->skipQtyCheckFlag->getQtySkipQtyCheck();
        $this->skipQtyCheckFlag->setSkipQtyCheck(true);

        $quote = $this->quoteCreator->create($miraklOrder, $store);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->quoteManagement->submit($quote);

        // Save order taxes details
        $this->orderTaxesCreator->create($order, $miraklOrder);

        $this->skipQtyCheckFlag->setSkipQtyCheck($oldSkipQtyCheckFlag);

        return $order;
    }
}
