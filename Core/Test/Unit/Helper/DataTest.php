<?php
namespace MiraklSeller\Core\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use MiraklSeller\Core\Helper\Data as CoreHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group process
 * @group helper
 * @coversDefaultClass \MiraklSeller\Process\Helper\Data
 */
class ProcessTest extends TestCase
{
    /**
     * @var CoreHelper
     */
    protected $helper;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $context = $objectManager->getObject(Context::class);

        $apiHelper = $this->getMockBuilder(ApiHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $objectManager->getObject(CoreHelper::class, [
            'context' => $context,
            'apiHelper' => $apiHelper,
            'storeManager' => $storeManager,
        ]);
    }

    /**
     * @covers ::isDateValid
     * @param   string          $from
     * @param   string          $to
     * @param   \DateTime|null  $date
     * @param   bool            $expected
     * @dataProvider getIsDateValidDataProvider
     */
    public function testIsDateValid($from, $to, $date, $expected)
    {
        $this->assertSame($expected, $this->helper->isDateValid($from, $to, $date));
    }

    /**
     * @return  array
     */
    public function getIsDateValidDataProvider()
    {
        return [
            ['', '', null, true],
            ['', '', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '', null, true],
            ['2017-01-01', '2017-01-31', null, false],
            ['2017-09-10', '2017-09-08', null, false],
            ['', '2999-12-31', null, true],
            ['2017-09-12', '2018-09-12', new \DateTime('2018-01-01'), true],
            ['2017-09-12', '2018-09-12', new \DateTime('2019-01-01'), false],
            ['2017-01-01', '', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '', new \DateTime('2012-01-01'), false],
            ['', '2017-01-01', new \DateTime('2012-01-01'), true],
            ['', '2017-01-01', new \DateTime('2017-01-01'), true],
            ['', '2017-01-01', new \DateTime('2017-01-02'), false],
            ['2017-01-01', '2017-01-01', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '2017-01-01', new \DateTime('2016-12-31'), false],
        ];
    }
}