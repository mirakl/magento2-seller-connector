<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\Shipping\ShippingTypeWithDescriptionCollection;
use Mirakl\MMP\Shop\Request\Shipping\GetShippingTypesRequest;
use Mirakl\MMP\Common\Domain\Collection\Shipping\CarrierCollection;
use Mirakl\MMP\Shop\Request\Shipping\GetShippingCarriersRequest;
use MiraklSeller\Api\Model\Connection;

class Shipping extends Client\MMP
{
    /**
     * (SH12) List all active shipping methods
     *
     * @param   Connection  $connection
     * @param   string|null $locale
     * @return  ShippingTypeWithDescriptionCollection
     */
    public function getShippingTypes(Connection $connection, $locale = null)
    {
        $request = new GetShippingTypesRequest();
        $request->setLocale($this->validateLocale($connection, $locale));

        return $this->send($connection, $request);
    }

    /**
     * (SH21) List all carriers (sorted by sortIndex, defined in the BO)
     *
     * @param   Connection  $connection
     * @return  CarrierCollection
     */
    public function getCarriers(Connection $connection)
    {
        $request = new GetShippingCarriersRequest();

        return $this->send($connection, $request);
    }
}
