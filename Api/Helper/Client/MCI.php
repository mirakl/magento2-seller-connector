<?php
namespace MiraklSeller\Api\Helper\Client;

use Mirakl\MCI\Shop\Client\ShopApiClient;
use MiraklSeller\Api\Model\Connection;

/**
 * @method ShopApiClient getClient(Connection $connection)
 */
class MCI extends AbstractClient
{
    const AREA_NAME = 'MCI';

    /**
     * {@inheritdoc}
     */
    protected function getArea()
    {
        return self::AREA_NAME;
    }
}