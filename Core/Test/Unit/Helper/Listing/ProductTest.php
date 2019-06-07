<?php
namespace MiraklSeller\Core\Test\Unit\Helper\Listing;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as ProductHelper;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;
use MiraklSeller\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
 * @coversDefaultClass \MiraklSeller\Core\Helper\Listing\Product
 */
class ProductTest extends TestCase
{
    /**
     * @var ProductHelper
     */
    protected $helper;

    /**
     * @var array
     */
    protected $constructorEntityMock;

    protected function setUp()
    {
        $classes = [
            'context' => Context::class,
            'storeManager' => StoreManagerInterface::class,
            'productResource' => ProductResource::class,
            'offerResourceFactory' => OfferResourceFactory::class,
            'attributeCollectionFactory' => AttributeCollectionFactory::class,
            'productCollectionFactory' => ProductCollectionFactory::class,
            'config' => Config::class,
        ];

        $this->constructorEntityMock = [];
        foreach ($classes as $varName => $className) {
            $this->constructorEntityMock[$varName] = $this->getMockBuilder($className)
                ->disableOriginalConstructor()
                ->getMock();
        }

        $objectManager = new ObjectManager($this);
        $this->helper = $objectManager->getObject(ProductHelper::class, $this->constructorEntityMock);
    }

    /**
     * @covers ::getCategoryFromPaths
     * @param   array   $paths
     * @param   mixed   $expected
     * @dataProvider getGetCategoryFromPathsDataProvider
     */
    public function testGetCategoryFromPaths(array $paths, $expected)
    {
        $this->assertSame($expected, $this->helper->getCategoryFromPaths($paths));
    }

    /**
     * @return  array
     */
    public function getGetCategoryFromPathsDataProvider()
    {
        return [
            [
                [],
                false,
            ],
            [
                [
                    ['foo', 'bar', 'baz'],
                    ['foo', 'bar'],
                ],
                ['foo', 'bar', 'baz']
            ],
            [
                [
                    ['b', 'foo', 'bar', 'baz'],
                    ['a', 'foo', 'bar', 'baz'],
                ],
                ['a', 'foo', 'bar', 'baz']
            ],
            [
                [
                    ['b', 'foo', 'bar', 'baz'],
                    ['a', 'foo', 'bar', 'baz'],
                    ['Lorem', 'ipsum', 'dolor', 'sit', 'amet'],
                    ['Lorem', 'ipsum'],
                ],
                ['Lorem', 'ipsum', 'dolor', 'sit', 'amet']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'B', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'B', 'B', 'D']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'C', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'A', 'C', 'D']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'C', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'A', 'C', 'D']
            ],
        ];
    }
}