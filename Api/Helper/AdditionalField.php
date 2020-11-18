<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldCollection;
use Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest;
use MiraklSeller\Api\Model\Connection;

class AdditionalField extends Client\MMP
{
    /**
     * (AF01) Get the list of any additional fields
     *
     * @param   Connection  $connection
     * @param   array       $entities   For example: ['OFFER', 'SHOP']
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getAdditionalFields(Connection $connection, $entities, $locale = null)
    {
        $request = new GetAdditionalFieldRequest();
        $request->setEntities($entities);
        $request->setLocale($this->validateLocale($connection, $locale));

        $this->_eventManager->dispatch('mirakl_seller_api_additional_fields_before', [
            'request' => $request,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * @param   Connection  $connection
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getOfferAdditionalFields(Connection $connection, $locale = null)
    {
        return $this->getAdditionalFields($connection, ['OFFER'], $locale);
    }
}
