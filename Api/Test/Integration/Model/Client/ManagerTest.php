<?php
namespace MiraklSeller\Api\Test\Integration\Model\Client;

use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @coversDefaultClass \MiraklSeller\Api\Model\Client\Manager
 */
class ManagerTest extends TestCase
{
    /**
     * @covers ::get
     */
    public function testGetMethod()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $clientManager = $objectManager->get(\MiraklSeller\Api\Model\Client\Manager::class);

        /** @var \MiraklSeller\Api\Model\Connection $connection */
        $connection = $objectManager->create(\MiraklSeller\Api\Model\Connection::class);

        $connection->setId(1);
        $connection->setName('Test 1');
        $connection->setApiUrl('https://test1.mirakl.net/api');
        $connection->setApiKey('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx');

        $mci1 = $clientManager->get($connection, 'MCI');
        $mci2 = $clientManager->get($connection, 'MCI');
        $this->assertSame($mci1, $mci2);

        $connection->setId(2);
        $mci3 = $clientManager->get($connection, 'MCI');
        $this->assertNotSame($mci1, $mci3);

        $mmp1 = $clientManager->get($connection, 'MMP');
        $this->assertNotSame($mci2, $mmp1);

        $mmp2 = $clientManager->get($connection, 'MMP');
        $this->assertSame($mmp1, $mmp2);

        $connection->setId(3);
        $mmp3 = $clientManager->get($connection, 'MMP');
        $this->assertNotSame($mmp1, $mmp3);

        $connection->setName('Test 2');
        $mmp4 = $clientManager->get($connection, 'MMP');
        $this->assertSame($mmp3, $mmp4);
    }
}