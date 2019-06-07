<?php
namespace MiraklSeller\Core\Test\Unit\Model\Listing\Export\Formatter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Data as Helper;
use MiraklSeller\Core\Helper\Inventory;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Offer as OfferFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller\Core\Model\Listing\Export\Formatter\Offer
 */
class OfferTest extends TestCase
{
    /**
     * @var OfferFormatter
     */
    protected $formatter;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var Inventory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventory;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['isDateValid'])
            ->getMock();

        $this->config = $this->createMock(Config::class);
        $this->inventory = $this->createMock(Inventory::class);

        $objectManager = new ObjectManager($this);
        $this->formatter = $objectManager->getObject(OfferFormatter::class, [
            'helper' => $this->helper,
            'config' => $this->config,
            'inventory' => $this->inventory
        ]);
    }

    /**
     * @covers ::format
     */
    public function testFormat()
    {
        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(Listing::class);

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connectionMock */
        $connectionMock = $this->createMock(Connection::class);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $this->config->expects($this->any())
            ->method('getOfferFieldsMapping')
            ->willReturn([]);

        $this->config->expects($this->any())
            ->method('isPromotionPriceExported')
            ->willReturn(false);

        $this->inventory->expects($this->any())
            ->method('isEnabledQtyIncrements')
            ->willReturn(0);

        $this->inventory->expects($this->any())
            ->method('getMinSaleQuantity')
            ->willReturn(0);

        $this->inventory->expects($this->any())
            ->method('getMaxSaleQuantity')
            ->willReturn(1000);

        $expectedKeys = [
            'sku',
            'product-id',
            'product-id-type',
            'description',
            'internal-description',
            'price',
            'price-additional-info',
            'quantity',
            'min-quantity-alert',
            'state',
            'available-start-date',
            'available-end-date',
            'logistic-class',
            'favorite-rank',
            'discount-price',
            'discount-start-date',
            'discount-end-date',
            'discount-ranges',
            'min-order-quantity',
            'max-order-quantity',
            'package-quantity',
            'leadtime-to-ship',
            'allow-quote-requests',
            'update-delete',
            'price-ranges',
            'product-tax-code',
            'entity_id',
        ];

        $data = [
            'sku'                       => 'ABCDEF-123',
            'product-id'                => null,
            'product-id-type'           => null,
            'description'               => 'Lorem ipsum dolor sit amet',
            'internal_description'      => null,
            'price'                     => 259.21,
            'price_additional_info'     => null,
            'final_price'               => 259.21,
            'available_start_date'      => null,
            'available_end_date'        => null,
            'special_price'             => null,
            'special_from_date'         => null,
            'special_to_date'           => null,
            'use_config_min_sale_qty'   => 1,
            'min_sale_qty'              => 0,
            'use_config_max_sale_qty'   => 1,
            'max_sale_qty'              => 0,
            'use_config_enable_qty_inc' => 1,
            'enable_qty_increments'     => 0,
            'use_config_qty_increments' => 1,
            'qty_increments'            => 0,
            'qty'                       => 12,
            'tier_prices'               => '',
            'min_quantity_alert'        => null,
            'state'                     => null,
            'leadtime_to_ship'          => null,
            'price_ranges'              => null,
            'product_tax_code'          => null,
            'entity_id'                 => 1,
            'logistic_class'            => '',
        ];

        $this->assertSame($expectedKeys, array_keys($this->formatter->format($data, $listingMock)));
    }

    /**
     * @covers ::computePromotion
     * @param   float   $basePrice
     * @param   float   $finalPrice
     * @param   float   $specialPrice
     * @param   string  $specialFromDate
     * @param   string  $specialToDate
     * @param   bool    $isPromoPriceExported
     * @param   array   $expected
     * @dataProvider getComputePromotionDataProvider
     */
    public function testComputePromotion(
        $basePrice, $finalPrice, $specialPrice, $specialFromDate, $specialToDate, $isPromoPriceExported, $expected
    ) {
        $this->config->expects($this->once())
            ->method('isPromotionPriceExported')
            ->willReturn($isPromoPriceExported);

        $computedPromotion = $this->formatter->computePromotion(
            $basePrice, $finalPrice, $specialPrice, $specialFromDate, $specialToDate
        );
        $this->assertSame($expected, $computedPromotion);
    }

    /**
     * @return  array
     */
    public function getComputePromotionDataProvider()
    {
        return [
            [99, 99, 0, '', '', true, [ // No promotion rule, no special price
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 99, 99, '', '', true, [ // No promotion rule, special price = base price, ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 79, 0, '', '', true, [ // Promotion rule applied, no special price
                'discount_price'      => '79.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 79, 0, '2012-01-01', '2999-12-31', true, [ // Promotion rule applied, no special price, must ignore date range
                'discount_price'      => '79.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 149.5, 149.5, '', '', true, [ // Promotion rule applied or valid special price applied
                'discount_price'      => '149.50',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 150, '', '', true, [ // Promotion rule applied because lower than special price
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 150, '2017-01-01', '', true, [ // Promotion rule applied because lower than special price, ignore date range
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 120, '2017-01-01', '', true, [ // Promotion rule maybe applied and equals to special price, must fill start date and discount price
                'discount_price'      => '120.00',
                'discount_start_date' => '2017-01-01',
                'discount_end_date'   => '',
            ]],
            [190, 120, 120, '', '2999-12-31', true, [ // Special price applied with valid end date, must fill end date
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '2999-12-31',
            ]],
            [49, 49, 29, '', '2012-12-31', true, [ // No promotion rule, invalid special price date, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [29, 19.9, 19.9, '2012-08-31', '2123-12-31', true, [ // Special price applied with valid date range, fill all fields
                'discount_price'      => '19.90',
                'discount_start_date' => '2012-08-31',
                'discount_end_date'   => '2123-12-31',
            ]],
            [49, 19, 29, '', '', false, [ // There is a promotion price but not allowed in config, use special price
                'discount_price'      => '29.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [49, 19, 29, '2012-08-31', '2123-12-31', false, [ // There is a promotion price but not allowed in config, use special price with date ranges
                'discount_price'      => '29.00',
                'discount_start_date' => '2012-08-31',
                'discount_end_date'   => '2123-12-31',
            ]],
            [49, 49, 49, '2012-08-31', '2123-12-31', false, [ // Valid special price date ranges but invalid special price, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [49, 19, 49, '2012-08-31', '', false, [ // Valid promotion price but not allowed in config and valid special price date ranges but invalid special price, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [224.9, 199, 199, '2017-01-01', '', false, [ // Promotion rule maybe applied but not allowed in config but equals to special price, must fill start date and discount price
                'discount_price'      => '199.00',
                'discount_start_date' => '2017-01-01',
                'discount_end_date'   => '',
            ]],
        ];
    }
}