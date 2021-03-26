<?php
namespace MiraklSeller\Sales\Helper;

class Config extends \MiraklSeller\Api\Helper\Config
{
    const XML_PATH_AUTO_ACCEPT_ORDERS_ENABLED              = 'mirakl_seller_sales/order_acceptance/auto_accept';
    const XML_PATH_AUTO_ACCEPT_INSUFFICIENT_STOCK_BEHAVIOR = 'mirakl_seller_sales/order_acceptance/insufficient_stock';
    const XML_PATH_AUTO_ACCEPT_BACKORDER_BEHAVIOR          = 'mirakl_seller_sales/order_acceptance/backorder';
    const XML_PATH_AUTO_ACCEPT_PRICES_VARIATIONS_BEHAVIOR  = 'mirakl_seller_sales/order_acceptance/prices_variations';

    const XML_PATH_AUTO_CREATE_INVOICE                 = 'mirakl_seller_sales/order/auto_create_invoice';
    const XML_PATH_AUTO_PAY_INVOICE                    = 'mirakl_seller_sales/order/auto_pay_invoice';
    const XML_PATH_AUTO_CREATE_SHIPMENT                = 'mirakl_seller_sales/order/auto_create_shipment';
    const XML_PATH_AUTO_CREATE_REFUNDS                 = 'mirakl_seller_sales/order/auto_create_refunds';
    const XML_PATH_AUTO_ORDERS_IMPORT                  = 'mirakl_seller_sales/order/auto_orders_import';
    const XML_PATH_AUTO_ORDERS_IMPORT_ALLOWED_STATUSES = 'mirakl_seller_sales/order/auto_orders_import_allowed_statuses';

    /**
     * Returns the Mirakl order statuses allowed for orders import in Magento
     *
     * @return  string[]
     */
    public function getAllowedStatusesForOrdersImport()
    {
        $statuses = $this->getValue(self::XML_PATH_AUTO_ORDERS_IMPORT_ALLOWED_STATUSES);

        return strlen($statuses) ? explode(',', $statuses) : [];
    }

    /**
     * Returns behavior selected during order auto acceptance process when a product has backorder enabled
     *
     * @see \MiraklSeller\Sales\Model\MiraklOrder\Acceptance\Backorder
     *
     * @return  int
     */
    public function getBackorderBehavior()
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_AUTO_ACCEPT_BACKORDER_BEHAVIOR);
    }

    /**
     * Returns behavior selected during order auto acceptance process when a product has not enough stock
     *
     * @see \MiraklSeller\Sales\Model\MiraklOrder\Acceptance\InsufficientStock
     *
     * @return  int
     */
    public function getInsufficientStockBehavior()
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_AUTO_ACCEPT_INSUFFICIENT_STOCK_BEHAVIOR);
    }

    /**
     * Returns percentage of price variation allowed during order auto acceptance process
     * when a product has a falling price difference between Mirakl and Magento.
     *
     * Return null = do not care of price difference
     * Return 0% = do not accept any price difference
     * Return 10% = allow Mirakl price to be 10% lower maximum compare to Magento price
     *
     * @return  int|null
     */
    public function getPricesVariationsPercent()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_AUTO_ACCEPT_PRICES_VARIATIONS_BEHAVIOR);

        if (null === $value || '' === $value) {
            return null;
        }

        return min((int) $value, 100);
    }

    /**
     * Returns true if auto acceptance of Mirakl orders is enabled
     *
     * @return  bool
     */
    public function isAutoAcceptOrdersEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_ACCEPT_ORDERS_ENABLED);
    }

    /**
     * Returns true if invoice has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateInvoice()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_CREATE_INVOICE);
    }

    /**
     * Returns true if refunds has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateRefunds()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_CREATE_REFUNDS);
    }

    /**
     * Returns true if shipment has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateShipment()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_CREATE_SHIPMENT);
    }

    /**
     * Returns true if Mirakl orders have to be automatically imported into Magento via Magento cron tasks
     *
     * @return  bool
     */
    public function isAutoOrdersImport()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_ORDERS_IMPORT);
    }

    /**
     * Returns true if invoice has to be payed automatically or not.
     * This is only available for pay on delivery and pay on due date orders.
     *
     * @return  bool
     */
    public function isAutoPayInvoice()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_PAY_INVOICE);
    }
}
