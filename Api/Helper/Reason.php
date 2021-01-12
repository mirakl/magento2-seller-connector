<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Shop\Domain\Collection\Reason\ReasonCollection;
use Mirakl\MMP\Shop\Request\Reason\GetTypeReasonsRequest;
use MiraklSeller\Api\Model\Connection;

class Reason extends Client\MMP
{
    /**
     * (RE02) Fetches reasons by type
     *
     * @param   Connection  $connection
     * @param   string      $type
     * @param   string|null $locale
     * @return  ReasonCollection
     */
    public function getTypeReasons(Connection $connection, $type = ReasonType::ORDER_MESSAGING, $locale = null)
    {
        $request = new GetTypeReasonsRequest($type);
        $request->setLocale($this->validateLocale($connection, $locale));

        return $this->send($connection, $request);
    }
}
