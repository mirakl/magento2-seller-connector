<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Shop\Domain\Collection\Reason\ReasonCollection;
use Mirakl\MMP\Shop\Request\Reason\GetReasonsRequest;
use MiraklSeller\Api\Model\Connection;

class Reason extends Client\MMP
{
    /**
     * @var array
     */
    private $reasonsByType = [];

    /**
     * (RE01) Fetches reasons from Mirakl platform that can be used for order messaging, etc.
     *
     * @param   Connection  $connection
     * @param   string|null $locale
     * @return  ReasonCollection
     */
    public function getReasons(Connection $connection, $locale = null)
    {
        $request = new GetReasonsRequest();
        $request->setLocale($this->validateLocale($connection, $locale));

        return $this->send($connection, $request);
    }

    /**
     * Returns reasons by type
     *
     * @param   Connection  $connection
     * @param   string      $type
     * @param   string|null $locale
     * @return  ReasonCollection
     */
    public function getTypeReasons(Connection $connection, $type = ReasonType::ORDER_MESSAGING, $locale = null)
    {
        if (!isset($this->reasonsByType[$type])) {
            $reasons = $this->getReasons($connection, $locale);
            $reasonCollection = new ReasonCollection();

            /** @var \Mirakl\MMP\Shop\Domain\Reason $reason */
            foreach ($reasons as $reason) {
                if ($reason->getType() === $type && $reason->getShopRight() === true) {
                    $reasonCollection->add($reason);
                }
            }

            $this->reasonsByType[$type] = $reasonCollection;
        }

        return $this->reasonsByType[$type];
    }
}
