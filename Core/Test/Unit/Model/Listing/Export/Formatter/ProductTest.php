<?php
namespace MiraklSeller\Core\Test\Unit\Model\Listing\Export\Formatter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as Helper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Export\Formatter\Product as ProductFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller\Core\Model\Listing\Export\Formatter\Product
 */
class ProductTest extends TestCase
{
    /**
     * @var ProductFormatter
     */
    protected $formatter;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    /**
     * @var Helper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->config = $this->createMock(Config::class);

        $objectManager = new ObjectManager($this);
        $this->formatter = $objectManager->getObject(ProductFormatter::class, [
            'helper' => $this->helper,
            'config' => $this->config
        ]);
    }

    /**
     * @covers ::format
     */
    public function testFormat()
    {
        $this->config->expects($this->any())
            ->method('getNumberImageMaxToExport')
            ->willReturn(1);

        /** @var Listing|\PHPUnit\Framework\MockObject\MockObject $listingMock */
        $listingMock = $this->getMockBuilder(Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn([]);
        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $expectedKeys = [
            'image_1',
            'category',
            'variant_group_code',
        ];

        $data = [
            'sku'         => 'ABCDEF-123',
            'description' => 'Lorem ipsum dolor sit amet',
            'color'       => 'Blue',
            'size'        => 'XL',
        ];

        $this->assertSame($expectedKeys, array_keys($this->formatter->format($data, $listingMock)));
    }
}
