<?php
namespace MiraklSeller\Core\Helper;

use MiraklSeller\Api\Helper\Data as ApiHelper;

class Data extends ApiHelper
{
    /**
     * Returns true if specified $date is valid compared to specified date range (from => to)
     *
     * @param   string          $from
     * @param   string          $to
     * @param   \DateTime|null  $date
     * @return  bool
     */
    public function isDateValid($from, $to, \DateTime $date = null)
    {
        if (!$from && !$to) {
            return true;
        }

        $currentDate = null !== $date ? $date : new \DateTime('today');

        if (!$from) {
            $isValid = $currentDate <= new \DateTime($to);
        } elseif (!$to) {
            $isValid = $currentDate >= new \DateTime($from);
        } else {
            $isValid = $currentDate >= new \DateTime($from) && $currentDate <= new \DateTime($to);
        }

        return $isValid;
    }

    /**
     * @return  bool
     */
    public static function isEnterprise()
    {
        return class_exists('Magento\Enterprise\Model\ProductMetadata');
    }

    /**
     * @return  bool
     */
    public function isMsiEnabled()
    {
        return $this->isModuleOutputEnabled('Magento_InventorySalesApi');
    }
}
