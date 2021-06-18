<?php
namespace MiraklSeller\Core\Test\Integration\Helper\Listing;

use MiraklSeller\Core\Helper\Data as DataHelper;
use MiraklSeller\Core\Test\Integration\TestCase;

/**
 * @group core
 * @group helper
 * @coversDefaultClass \MiraklSeller\Core\Helper\Data
 */
class DataTest extends TestCase
{
    /**
     * @var DataHelper
     */
    protected $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = $this->objectManager->create(DataHelper::class);
    }

    /**
     * @covers ::isMsiEnabled
     */
    public function testIsMsiEnabled()
    {
        $this->assertTrue($this->helper->isMsiEnabled());
    }
}
