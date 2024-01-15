<?php
namespace MiraklSeller\Sales\Model\Create;

use Magento\Framework\App\Language\Dictionary;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Tax\Item as OrderTaxItem;
use Magento\Sales\Model\Order\Tax\ItemFactory as OrderTaxItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax as OrderTaxResource;
use Magento\Sales\Model\ResourceModel\Order\TaxFactory as OrderTaxResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as OrderTaxItemResource;
use Magento\Sales\Model\ResourceModel\Order\Tax\ItemFactory as OrderTaxItemResourceFactory;
use Magento\Tax\Model\Sales\Order\Tax as OrderTax;
use Magento\Tax\Model\Sales\Order\TaxFactory as OrderTaxFactory;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class OrderTaxes
{
    /**
     * @var OrderTaxFactory
     */
    protected $orderTaxFactory;

    /**
     * @var OrderTaxItemFactory
     */
    protected $orderTaxItemFactory;

    /**
     * @var OrderTaxResourceFactory
     */
    protected $orderTaxResourceFactory;

    /**
     * @var OrderTaxItemResourceFactory
     */
    protected $orderTaxItemResourceFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var Dictionary
     */
    protected $dictionary;

    /**
     * @param OrderTaxFactory             $orderTaxFactory
     * @param OrderTaxItemFactory         $orderTaxItemFactory
     * @param OrderTaxResourceFactory     $orderTaxResourceFactory
     * @param OrderTaxItemResourceFactory $orderTaxItemResourceFactory
     * @param OrderHelper                 $orderHelper
     * @param Dictionary                  $dictionary
     */
    public function __construct(
        OrderTaxFactory $orderTaxFactory,
        OrderTaxItemFactory $orderTaxItemFactory,
        OrderTaxResourceFactory $orderTaxResourceFactory,
        OrderTaxItemResourceFactory $orderTaxItemResourceFactory,
        OrderHelper $orderHelper,
        Dictionary $dictionary
    ) {
        $this->orderTaxFactory             = $orderTaxFactory;
        $this->orderTaxItemFactory         = $orderTaxItemFactory;
        $this->orderTaxResourceFactory     = $orderTaxResourceFactory;
        $this->orderTaxItemResourceFactory = $orderTaxItemResourceFactory;
        $this->orderHelper                 = $orderHelper;
        $this->dictionary                  = $dictionary;
    }

    /**
     * @param   Order       $order
     * @param   ShopOrder   $miraklOrder
     * @return  $this
     */
    public function create(Order $order, ShopOrder $miraklOrder)
    {
        $itemsTaxes = $this->orderHelper->getMiraklOrderTaxDetails($miraklOrder);
        $computedTaxes = $this->orderHelper->getMiraklOrderTaxDetailsComputed($miraklOrder);

        /** @var OrderTaxResource $orderTaxResource */
        $orderTaxResource = $this->orderTaxResourceFactory->create();

        /** @var OrderTaxItemResource $orderTaxItemResource */
        $orderTaxItemResource = $this->orderTaxItemResourceFactory->create();

        $orderLocale = $this->orderHelper->getOrderLocale($order);
        $title = $this->dictionary->getDictionary($orderLocale)['Tax Rate'] ?? 'VAT';

        // Save order taxes by code
        foreach ($computedTaxes as $computedTaxesCode => $computedTaxByRate) {
            $i = 1;
            foreach ($computedTaxByRate as $computedTax) {
                $data = [
                    'order_id'         => $order->getId(),
                    'code'             => $computedTaxesCode . '_' . $i++,
                    'title'            => $title,
                    'hidden'           => 0,
                    'percent'          => $computedTax['rate'],
                    'priority'         => 0,
                    'position'         => 0,
                    'process'          => 0,
                    'amount'           => $computedTax['amount'],
                    'base_amount'      => $computedTax['amount'],
                    'base_real_amount' => $computedTax['amount'],
                ];

                /** @var OrderTax $orderTax */
                $orderTax = $this->orderTaxFactory->create();
                $orderTax->setData($data);
                $orderTaxResource->save($orderTax);

                // Save order item taxes by code
                foreach ($itemsTaxes as $taxableItemType => $itemTaxDetails) {
                    foreach ($itemTaxDetails as $sku => $taxDetails) {
                        if (null === ($orderItem = $this->orderHelper->getOrderItemBySku($order, $sku))) {
                            continue;
                        }

                        foreach ($taxDetails as $taxDetailsCode => $taxByRate) {
                            foreach ($taxByRate as $tax) {
                                if ($taxDetailsCode !== $computedTaxesCode || $tax['rate'] != $computedTax['rate']) {
                                    continue;
                                }

                                $data = [
                                    'item_id'            => $taxableItemType == 'product' ? $orderItem->getId() : null,
                                    'tax_id'             => $orderTax->getId(),
                                    'tax_percent'        => $tax['rate'],
                                    'associated_item_id' => null,
                                    'amount'             => $tax['amount'],
                                    'base_amount'        => $tax['amount'],
                                    'real_amount'        => $tax['amount'],
                                    'real_base_amount'   => $tax['amount'],
                                    'taxable_item_type'  => $taxableItemType,
                                ];

                                /** @var OrderTaxItem $orderTaxItem */
                                $orderTaxItem = $this->orderTaxItemFactory->create();
                                $orderTaxItem->setData($data);
                                $orderTaxItemResource->save($orderTaxItem);
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }
}
