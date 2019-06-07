<?php
namespace MiraklSeller\Sales\Test\Integration\Helper\Loader;

use MiraklSeller\Api\Test\Integration\TestCase;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

class ConnectionTest extends TestCase
{
    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    protected function setUp()
    {
        parent::setUp();
        $this->connectionLoader = $this->objectManager->create(ConnectionLoader::class);
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Sales/Test/Integration/Helper/Loader/_fixtures/connection.php
     */
    public function testGetCurrentConnection()
    {
        $this->assertSame('Test Connection #1', $this->connectionLoader->getCurrentConnection()->getName());
    }

    /**
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-seller/Sales/Test/Integration/Helper/Loader/_fixtures/connection.php
     */
    public function testGetConnections()
    {
        $this->assertCount(2, $this->connectionLoader->getConnections());
    }
}