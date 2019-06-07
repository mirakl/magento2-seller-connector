<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Shop\Domain\Shop\ShopAccount;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;
use MiraklSeller\Api\Model\Connection;

class Shop extends Client\MMP
{
    /**
     * (A01) Get shop information
     *
     * @param   Connection  $connection
     * @return  ShopAccount
     */
    public function getAccount(Connection $connection)
    {
        return $this->send($connection, new GetAccountRequest());
    }
}