<?php
namespace Mirakl\Test\Integration\Core\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;
use MiraklSeller\Core\Model\ResourceModel\Product\Collection as ProductCollection;
use MiraklSeller\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use MiraklSeller\Core\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group resource
 * @group collection
 * @coversDefaultClass \MiraklSeller\Core\Model\ResourceModel\Product\Collection
 */
class CollectionTest extends TestCase
{
    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var ProductCollection
     */
    protected $productCollection;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productResource = $this->objectManager->create(ProductResource::class);
        $this->productCollection = $this->objectManager->create(ProductCollectionFactory::class)->create();
        $this->attributeFactory = $this->objectManager->create(AttributeFactory::class);
    }

    /**
     * @covers ::addAttributeOptionValue
     * @param   array   $productIds
     * @param   array   $attributeCodes
     * @param   array   $expectedItems
     * @dataProvider getTestAddAttributeOptionValueDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAddAttributeOptionValue($productIds, $attributeCodes, $expectedItems)
    {
        $this->productCollection->addIdFilter($productIds);

        foreach ($attributeCodes as $attrCode) {
            $this->productCollection->addAttributeOptionValue($this->getAttribute($attrCode));
        }
        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddAttributeOptionValueDataProvider()
    {
        return [
            [
                [540],
                ['color'],
                [
                    540 => ['entity_id' => '540', 'color' => 'Green'],
                ]
            ],
            [
                [882],
                ['color', 'size'],
                [
                    882 => ['entity_id' => '882', 'color' => 'Blue', 'size' => '32'],
                ]
            ],
            [
                [13, 21],
                ['activity', 'material', 'category_gear'],
                [
                    13 => ['entity_id' => '13', 'activity' => 'Overnight', 'material' => 'Leather', 'category_gear' => null],
                    21 => ['entity_id' => '21', 'activity' => 'Yoga', 'material' => 'Foam', 'category_gear' => 'Exercise'],
                ]
            ],
        ];
    }

    /**
     * @covers ::addListingPriceData
     * @param   array   $productIds
     * @param   int     $storeId
     * @param   array   $expectedItems
     * @dataProvider getTestAddListingPriceDataDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAddListingPriceData($productIds, $storeId, $expectedItems)
    {
        $listing = $this->createSampleListing();
        $listing->getConnection()->setStoreId($storeId);

        $this->productCollection
            ->addListingPriceData($listing)
            ->addIdFilter($productIds);

        $this->assertSame($storeId, $this->productCollection->getStoreId());

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddListingPriceDataDataProvider()
    {
        return [
            [
                337, 0, [
                    337 => [
                        'entity_id' => '337', 'price' => '65.000000', 'tax_class_id' => '2', 'final_price' => '65.000000',
                        'minimal_price' => '65.000000', 'min_price' => '65.000000', 'max_price' => '65.000000',
                        'tier_price' => null
                    ],
                ],
            ],
            [
                384, 1, [
                    384 => [
                        'entity_id' => '384', 'price' => '56.990000', 'tax_class_id' => '2', 'final_price' => '56.990000',
                        'minimal_price' => '56.990000', 'min_price' => '56.990000', 'max_price' => '56.990000',
                        'tier_price' => null
                    ],
                ],
            ],
            [
                123456789, 1, []
            ],
        ];
    }

    /**
     * @covers ::addQuantityToSelect
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddQuantityToSelectDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAddQuantityToSelect($productIds, $expectedItems)
    {
        $this->productCollection
            ->addQuantityToSelect()
            ->addIdFilter($productIds);

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddQuantityToSelectDataProvider()
    {
        return [
            [551, [551 => [
                'entity_id' => '551', 'qty' => '100.0000', 'use_config_min_sale_qty' => '1',
                'use_config_max_sale_qty' => '1', 'use_config_enable_qty_inc' => '1',
                'use_config_qty_increments' => '1', 'min_sale_qty' => '1.0000',
                'max_sale_qty' => '0.0000', 'enable_qty_increments' => '0','qty_increments' => '0.0000',
            ]]],
            [378, [378 => [
                'entity_id' => '378', 'qty' => '100.0000', 'use_config_min_sale_qty' => '1',
                'use_config_max_sale_qty' => '1', 'use_config_enable_qty_inc' => '1',
                'use_config_qty_increments' => '1', 'min_sale_qty' => '1.0000',
                'max_sale_qty' => '0.0000', 'enable_qty_increments' => '0','qty_increments' => '0.0000',
            ]]],
        ];
    }

    /**
     * @covers ::addTierPricesToSelect
     * @param   int     $websiteId
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddTierPricesToSelectDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_special_price.php
     * @magentoDbIsolation enabled
     */
    public function testAddTierPricesToSelect($websiteId, $productIds, $expectedItems)
    {
        $this->productCollection
            ->addTierPricesToSelect($websiteId)
            ->addIdFilter($productIds);

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddTierPricesToSelectDataProvider()
    {
        return [
            [1, 286, [286 => ['entity_id' => '286', 'tier_prices' => '']]],
            [1, 287, [287 => ['entity_id' => '287', 'tier_prices' => '10|32.00,20|25.00,5|36.00']]],
            [1, 288, [288 => ['entity_id' => '288', 'tier_prices' => '10|32.00,5|33.00']]],
            [1, 289, [289 => ['entity_id' => '289', 'tier_prices' => '15|37.00,5|39.00']]],
        ];
    }

    /**
     * @coversNothing
     * @param   array   $productIds
     * @param   int     $storeId
     * @param   array   $attributeCodes
     * @param   array   $expectedItems
     * @dataProvider getTestAllFiltersTogetherDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAllFiltersTogether($productIds, $storeId, $attributeCodes, $expectedItems)
    {
        $listing = $this->createSampleListing();
        $listing->getConnection()->setStoreId($storeId);


        foreach ($attributeCodes as $attrCode) {
            $this->productCollection->addAttributeOptionValue($this->getAttribute($attrCode));
        }

        $this->productCollection
            ->addQuantityToSelect()
            ->addTierPricesToSelect($listing->getWebsiteId())
            ->addListingPriceData($listing)
            ->addIdFilter($productIds);

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAllFiltersTogetherDataProvider()
    {
        return [
            [
                11, 1, ['color', 'activity', 'style_bags'], [
                    11 => [
                        'entity_id' => '11', 'color' => null, 'activity' => 'Gym', 'style_bags' => 'Backpack',
                        'qty' => '100.0000', 'use_config_min_sale_qty' => '1', 'use_config_max_sale_qty' => '1',
                        'use_config_enable_qty_inc' => '1', 'use_config_qty_increments' => '1',
                        'min_sale_qty' => '1.0000', 'max_sale_qty' => '0.0000', 'enable_qty_increments' => '0',
                        'qty_increments' => '0.0000', 'tier_prices' => '', 'price' => '33.000000',
                        'tax_class_id' => '2', 'final_price' => '33.000000', 'minimal_price' => '33.000000',
                        'min_price' => '33.000000', 'max_price' => '33.000000', 'tier_price' => null
                    ],
                ],
            ],
            [
                18, 1, ['color', 'size', 'activity'], [
                    18 => [
                        'entity_id' => '18', 'color' => null, 'size' => null, 'activity' => 'Gym',
                        'qty' => '100.0000', 'use_config_min_sale_qty' => '1', 'use_config_max_sale_qty' => '1',
                        'use_config_enable_qty_inc' => '1', 'use_config_qty_increments' => '1',
                        'min_sale_qty' => '1.0000', 'max_sale_qty' => '0.0000', 'enable_qty_increments' => '0',
                        'qty_increments' => '0.0000', 'tier_prices' => '', 'price' => '16.000000',
                        'tax_class_id' => '2', 'final_price' => '16.000000', 'minimal_price' => '16.000000',
                        'min_price' => '16.000000', 'max_price' => '16.000000', 'tier_price' => null
                    ],
                ],
            ],
            [
                361, 0, ['color', 'size', 'material'], [
                    361 => [
                        'entity_id' => '361', 'color' => 'Orange', 'size' => 'L', 'material' => null,
                        'qty' => '100.0000', 'use_config_min_sale_qty' => '1', 'use_config_max_sale_qty' => '1',
                        'use_config_enable_qty_inc' => '1', 'use_config_qty_increments' => '1',
                        'min_sale_qty' => '1.0000', 'max_sale_qty' => '0.0000', 'enable_qty_increments' => '0',
                        'qty_increments' => '0.0000', 'tier_prices' => '', 'price' => '66.000000',
                        'tax_class_id' => '2', 'final_price' => '66.000000', 'minimal_price' => '66.000000',
                        'min_price' => '66.000000', 'max_price' => '66.000000', 'tier_price' => null
                    ],
                ],
            ],
            [
                98656217, 1, ['luggage_size'], []
            ],
        ];
    }

    /**
     * @covers ::addCategoryIds
     * @param   array   $productIds
     * @param   bool    $fallbackToParent
     * @param   array   $expectedItems
     * @dataProvider getTestAddCategoryIdsDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_categories.php
     * @magentoDbIsolation enabled
     */
    public function testAddCategoryIds($productIds, $fallbackToParent, $expectedItems)
    {
        $this->productCollection->addIdFilter($productIds);
        $this->productCollection->load();
        $this->productCollection->addCategoryIds($fallbackToParent);

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddCategoryIdsDataProvider()
    {
        return [
            [
                [12, 14, 17], true, [
                    12 => ['entity_id' => '12', 'category_ids' => [3, 4]],
                    14 => ['entity_id' => '14', 'category_ids' => [3, 7, 4]],
                    17 => ['entity_id' => '17', 'category_ids' => [3, 5]],
                ],
            ],
            [
                [255, 256, 257], true, [
                    255 => ['entity_id' => '255', 'category_ids' => [14]],
                    256 => ['entity_id' => '256', 'category_ids' => [14]],
                    257 => ['entity_id' => '257', 'category_ids' => [14]],
                ],
            ],
            [
                [549, 550, 551, 552, 553, 554], true, [
                    549 => ['entity_id' => '549', 'category_ids' => [16, 12]],
                    550 => ['entity_id' => '550', 'category_ids' => [16, 12]],
                    551 => ['entity_id' => '551', 'category_ids' => [16]],
                    552 => ['entity_id' => '552', 'category_ids' => [16]],
                    553 => ['entity_id' => '553', 'category_ids' => [16]],
                    554 => ['entity_id' => '554', 'category_ids' => [16]],
                ],
            ],
            [
                [549, 550, 551, 552, 553, 554, 555], false, [
                    549 => ['entity_id' => '549'],
                    550 => ['entity_id' => '550'],
                    551 => ['entity_id' => '551', 'category_ids' => [16]],
                    552 => ['entity_id' => '552', 'category_ids' => [16]],
                    553 => ['entity_id' => '553', 'category_ids' => [16]],
                    554 => ['entity_id' => '554', 'category_ids' => [16]],
                    555 => ['entity_id' => '555', 'category_ids' => [16]],
                ],
            ],
            [
                [], true, [],
            ],
        ];
    }

    /**
     * @covers ::addCategoryNames
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddCategoryNamesDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAddCategoryNames($productIds, $expectedItems)
    {
        $this->productCollection->addIdFilter($productIds);
        $this->productCollection->load();
        $this->productCollection->addCategoryNames();

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddCategoryNamesDataProvider()
    {
        return [
            [
                [12, 14, 17], [
                    12 => ['entity_id' => '12', 'category_names' => ['Gear', 'Bags']],
                    14 => ['entity_id' => '14', 'category_names' => ['Gear', 'Collections', 'Bags']],
                    17 => ['entity_id' => '17', 'category_names' => ['Gear', 'Fitness Equipment']],
                ],
            ],
            [
                [876, 877, 878], [
                    876 => ['entity_id' => '876', 'category_names' => ['Pants', 'Pants', 'Default Category']],
                    877 => ['entity_id' => '877', 'category_names' => ['Pants', 'Pants', 'Default Category']],
                    878 => ['entity_id' => '878', 'category_names' => ['Pants', 'Pants', 'Default Category']],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @covers ::addParentSkus
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddParentSkusDataProvider
     * @magentoDbIsolation enabled
     */
    public function testAddParentSkus($productIds, $expectedItems)
    {
        $this->productCollection->addIdFilter($productIds);
        $this->productCollection->load();
        $this->productCollection->overrideByParentData(['parent_sku' => 'sku']);

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getTestAddParentSkusDataProvider()
    {
        return [
            [
                [12, 14, 17], [
                    12 => ['entity_id' => '12'],
                    14 => ['entity_id' => '14'],
                    17 => ['entity_id' => '17'],
                ],
            ],
            [
                [876, 877, 878], [
                    876 => ['entity_id' => '876', 'parent_sku' => 'MP12', 'parent_id' => '880'],
                    877 => ['entity_id' => '877', 'parent_sku' => 'MP12', 'parent_id' => '880'],
                    878 => ['entity_id' => '878', 'parent_sku' => 'MP12', 'parent_id' => '880'],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @covers ::addMediaGalleryAttribute
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getAddMediaGalleryAttributeDataProvider
     * @magentoConfigFixture current_store web/unsecure/base_url http://foobar.com/
     * @magentoDbIsolation enabled
     */
    public function testAddMediaGalleryAttribute($productIds, $expectedItems)
    {
        $this->productCollection->addIdFilter($productIds);
        $this->productCollection->load();
        $this->productCollection->addMediaGalleryAttribute();

        $data = $this->removeBaseAttribute($this->productCollection->getItems());

        $this->assertSame($expectedItems, $data);
    }

    /**
     * @return  array
     */
    public function getAddMediaGalleryAttributeDataProvider()
    {
        return [
            [
                [12, 14, 17], [
                    12 => ['entity_id' => '12', 'image_1' => 'http://foobar.com/pub/media/catalog/product/w/b/wb03-purple-0.jpg'],
                    14 => ['entity_id' => '14', 'image_1' => 'http://foobar.com/pub/media/catalog/product/w/b/wb04-blue-0.jpg'],
                    17 => ['entity_id' => '17', 'image_1' => 'http://foobar.com/pub/media/catalog/product/u/g/ug04-bk-0.jpg'],
                ],
            ],
            [
                [876, 877, 878], [
                    876 => ['entity_id' => '876', 'image_1' => 'http://foobar.com/pub/media/catalog/product/m/p/mp12-red_main_2.jpg'],
                    877 => ['entity_id' => '877', 'image_1' => 'http://foobar.com/pub/media/catalog/product/m/p/mp12-black_main_2.jpg'],
                    878 => ['entity_id' => '878', 'image_1' => 'http://foobar.com/pub/media/catalog/product/m/p/mp12-blue_main_2.jpg'],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @param   string  $attrCode
     * @return  Attribute
     */
    protected function getAttribute($attrCode)
    {
        return $this->attributeFactory->create()->loadByCode('catalog_product', $attrCode);
    }

    /**
     * @param   array   $data
     * @param   array   $exclude
     * @return  mixed
     */
    protected function removeBaseAttribute($data, $exclude = [])
    {
        $fields = $this->productResource->getProductBaseColumns();
        foreach (array_diff($fields, array_merge(['entity_id'], $exclude)) as $field) {
            foreach ($data as &$product) {
                unset($product[$field]);
            }
        }

        return $data;
    }
}
