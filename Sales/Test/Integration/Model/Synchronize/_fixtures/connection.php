<?php

use MiraklSeller\Api\Model\Connection;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var Connection $connection1 */
$connection1 = $objectManager->create(Connection::class);
$connection1
    ->setName('Test Connection #1')
    ->setApiUrl('https://fake-env.mirakl.net/api')
    ->setApiKey('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx')
    ->setStoreId(0);
$connection1->save();