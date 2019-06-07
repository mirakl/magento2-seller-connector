<?php
namespace Mirakl\Test\Integration\Core\Model\Listing\Export;

use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\Products as ExportModel;
use MiraklSeller\Core\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller\Core\Model\Listing\Export\Products
 */
class ProductsTest extends TestCase
{
    /**
     * @var ExportModel
     */
    protected $exportModel;

    protected function setUp()
    {
        parent::setUp();

        $this->exportModel = $this->objectManager->create(ExportModel::class);
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_different_parent.php
     * @magentoConfigFixture current_store web/unsecure/base_url http://foobar.com/
     * @magentoConfigFixture current_store mirakl_seller_core/listing/nb_image_exported 1
     * @magentoDbIsolation enabled
     */
    public function testExport($productIds, $expectedResult)
    {
        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn([]);

        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $result = $this->exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[341, 342, 343, 344, 345], $this->_getJsonFileContents('expected_export_products_2.json')],
            [[36, 37, 38], $this->_getJsonFileContents('expected_export_products_3.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $variantsAttributes
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithVariantsAttributesDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_different_parent.php
     * @magentoConfigFixture current_store web/unsecure/base_url http://foobar.com/
     * @magentoConfigFixture current_store mirakl_seller_core/listing/nb_image_exported 1
     * @magentoDbIsolation enabled
     */
    public function testExportWithVariantsAttributes($productIds, $variantsAttributes, $expectedResult)
    {
        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn([]);

        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);
        $listingMock->expects($this->any())
            ->method('getVariantsAttributes')
            ->willReturn($variantsAttributes);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $result = $this->exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithVariantsAttributesDataProvider()
    {
        return [
            [[267, 268, 269], ['color'], $this->_getJsonFileContents('expected_export_products_with_variants_attributes_1.json')],
            [[267, 268, 269], ['color', 'size'], $this->_getJsonFileContents('expected_export_products_with_variants_attributes_2.json')],
            [[267, 268, 269], ['name'], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[267, 268, 269], ['shoe_size'], $this->_getJsonFileContents('expected_export_products_1.json')],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $exportableAttributes
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithExportableAttributesDataProvider
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Core/Test/Integration/Model/_fixtures/products_with_different_parent.php
     * @magentoConfigFixture current_store web/unsecure/base_url http://foobar.com/
     * @magentoConfigFixture current_store mirakl_seller_core/listing/nb_image_exported 1
     * @magentoDbIsolation enabled
     */
    public function testExportWithExportableAttributes($productIds, $exportableAttributes, $expectedResult)
    {
        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn($exportableAttributes);

        /** @var Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $result = $this->exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithExportableAttributesDataProvider()
    {
        return [
            [[267, 268, 269], [], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[267, 268, 269], ['description', 'short_description'], $this->_getJsonFileContents('expected_export_products_with_exportable_attributes_1.json')],
        ];
    }
}