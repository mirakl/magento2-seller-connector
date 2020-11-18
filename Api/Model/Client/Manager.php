<?php
namespace MiraklSeller\Api\Model\Client;

use Mirakl\Core\Client\AbstractApiClient;
use MiraklSeller\Api\Model\Connection;

class Manager
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var AbstractApiClient[]
     */
    private static $clients = [];

    /**
     * @param   Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Disable all API clients
     */
    public static function disable()
    {
        foreach (self::$clients as $client) {
            $client->disable();
        }
    }

    /**
     * Enable all API clients
     */
    public static function enable()
    {
        foreach (self::$clients as $client) {
            $client->disable(false);
        }
    }

    /**
     * @param   Connection  $connection
     * @param   string      $area
     * @return  AbstractApiClient
     */
    public function get(Connection $connection, $area)
    {
        $hash = sha1(json_encode([$connection->getId(), $area]));
        if (!isset(self::$clients[$hash])) {
            self::$clients[$hash] = $this->factory->create(
                $connection->getApiUrl(),
                $connection->getApiKey(),
                $area,
                $connection->getShopId() ?: null
            );
        }

        return self::$clients[$hash];
    }
}
