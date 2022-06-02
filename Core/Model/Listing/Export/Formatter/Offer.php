<?php
namespace MiraklSeller\Core\Model\Listing\Export\Formatter;

use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Data as Helper;
use MiraklSeller\Core\Helper\Inventory;
use MiraklSeller\Core\Model\Listing;

class Offer implements FormatterInterface
{
    const DEFAULT_PRODUCT_ID_TYPE = 'SHOP_SKU';

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Inventory
     */
    protected $inventory;

    /**
     * @param   Helper      $helper
     * @param   Config      $config
     * @param   Inventory   $inventory
     */
    public function __construct(Helper $helper, Config $config, Inventory $inventory)
    {
        $this->helper = $helper;
        $this->config = $config;
        $this->inventory = $inventory;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $data, Listing $listing)
    {
        $dataPromotion = $this->computePromotion(
            $data['price'],
            isset($data['final_price']) ? $data['final_price'] : $data['price'],
            isset($data['special_price']) ? $data['special_price'] : 0,
            isset($data['special_from_date']) ? $data['special_from_date'] : '',
            isset($data['special_to_date']) ? $data['special_to_date'] : ''
        );

        foreach ($this->config->getOfferFieldsMapping($listing->getStoreId()) as $key => $value) {
            if (empty($value) || empty($data[$value])) {
                // Set empty value if no mapping defined or if mapping value is empty
                $data[$key] = '';
            } else {
                // Override default key by its configured mapping value
                $data[$key] = $data[$value];
            }
        }

        $dataPriceRanges = $this->computePriceRanges($data['tier_prices'], $listing);

        $packageQuantity = 1;
        if ($this->inventory->isEnabledQtyIncrements(
            $data['use_config_enable_qty_inc'],
            $data['enable_qty_increments']
        )) {
            $packageQuantity = max($packageQuantity, $this->inventory->getQtyIncrements(
                $data['use_config_qty_increments'],
                $data['qty_increments']
            ));
        }

        $exportedPricesAttr = $listing->getConnection()->getExportedPricesAttribute();
        if ($exportedPricesAttr && !empty($data[$exportedPricesAttr])) {
            // If specific price field is set on the connection, use it and reset Magento calculated prices
            $data['price'] = (float) $data[$exportedPricesAttr];
            $dataPromotion = [
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ];
            $dataPriceRanges = [
                'discount_ranges' => '',
                'price_ranges'    => '',
            ];
        }

        return [
            'sku'                   => $data['sku'],
            'product-id'            => $data['product-id'],
            'product-id-type'       => $data['product-id-type'],
            'description'           => $data['description'],
            'internal-description'  => $data['internal_description'],
            'price'                 => isset($data['price']) ? self::formatPrice($data['price']) : '0',
            'price-additional-info' => $data['price_additional_info'],
            'quantity'              => isset($data['qty']) ? max(0, intval($data['qty'])) : '',
            'min-quantity-alert'    => $data['min_quantity_alert'],
            'state'                 => $data['state'] ?: \MiraklSeller\Core\Model\Offer::DEFAULT_STATE,
            'available-start-date'  => self::formatDate($data['available_start_date']),
            'available-end-date'    => self::formatDate($data['available_end_date']),
            'logistic-class'        => $data['logistic_class'],
            'favorite-rank'         => '',
            'discount-price'        => $dataPromotion['discount_price'],
            'discount-start-date'   => $dataPromotion['discount_start_date'],
            'discount-end-date'     => $dataPromotion['discount_end_date'],
            'discount-ranges'       => $dataPriceRanges['discount_ranges'],
            'min-order-quantity'    => $this->inventory->getMinSaleQuantity($data['use_config_min_sale_qty'], $data['min_sale_qty']),
            'max-order-quantity'    => $this->inventory->getMaxSaleQuantity($data['use_config_max_sale_qty'], $data['max_sale_qty']),
            'package-quantity'      => $packageQuantity,
            'leadtime-to-ship'      => $data['leadtime_to_ship'],
            'allow-quote-requests'  => '',
            'update-delete'         => isset($data['action']) ? $data['action'] : 'update',
            'price-ranges'          => $dataPriceRanges['price_ranges'],
            'product-tax-code'      => $data['product_tax_code'],
            'entity_id'             => $data['entity_id'], // Extra column in order to get product id in error report
        ];
    }

    /**
     * @param   string  $value
     * @return  string
     */
    public static function formatDate($value)
    {
        return substr($value, 0, 10);
    }

    /**
     * @param   float   $price
     * @return  string
     */
    public static function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }

    /**
     * Returns discount price and dates based on configuration
     *
     * @param   float   $basePrice
     * @param   float   $finalPrice
     * @param   float   $specialPrice
     * @param   string  $specialFromDate
     * @param   string  $specialToDate
     * @return  array
     */
    public function computePromotion($basePrice, $finalPrice, $specialPrice, $specialFromDate = '', $specialToDate = '')
    {
        $specialPrice = isset($specialPrice) ? $specialPrice : 0;

        if (!empty($specialFromDate)) {
            $specialFromDate = self::formatDate($specialFromDate);
        }
        if (!empty($specialToDate)) {
            $specialToDate = self::formatDate($specialToDate);
        }

        $data = [
            'discount_price'      => '',
            'discount_start_date' => '',
            'discount_end_date'   => '',
        ];

        // Check if special date range is valid compared to current day
        $isSpecialDateValid = $this->helper->isDateValid($specialFromDate, $specialToDate);

        if ($this->config->isPromotionPriceExported()) {
            // Exporting promotion price is allowed in config
            // $finalPrice includes $specialPrice so using $finalPrice covers the following cases:
            // 1. there is an active special price on the product
            // 2. there is a promotion rule applied on the product
            // 3. both cases above
            if ($finalPrice < $basePrice) {
                $data['discount_price'] = self::formatPrice($finalPrice);
                if ($isSpecialDateValid && $specialPrice == $finalPrice) {
                    // Fill the discount dates if special price is the final price and is lower than base price
                    $data['discount_start_date'] = $specialFromDate;
                    $data['discount_end_date'] = $specialToDate;
                }
            }
        } elseif ($isSpecialDateValid && $specialPrice > 0 && $specialPrice < $basePrice) {
            // Exporting promotion price is NOT allowed in config
            // We fill the discount price only if special price is valid and lower than base price
            $data['discount_price'] = self::formatPrice($specialPrice);
            $data['discount_start_date'] = $specialFromDate;
            $data['discount_end_date'] = $specialToDate;
        }

        return $data;
    }

    /**
     * Compute price ranges with connection configuration
     *
     * @param   string  $tierPrices
     * @param   Listing $listing
     * @return  array
     */
    public function computePriceRanges($tierPrices, Listing $listing)
    {
        $data = [
            'price_ranges'    => '',
            'discount_ranges' => '',
        ];

        if (!empty($tierPrices)) {
            $connection = $listing->getConnection();
            if ($connection->getMagentoTierPricesApplyOn() == \MiraklSeller\Api\Model\Connection::VOLUME_PRICING) {
                $data['price_ranges'] = $tierPrices;
            } else {
                $data['discount_ranges'] = $tierPrices;
            }
        }

        return $data;
    }
}
