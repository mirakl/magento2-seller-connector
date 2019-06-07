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
        $fromDate    = new \DateTime($from);
        $toDate      = new \DateTime($to);

        if (!$from) {
            $isValid = $currentDate <= $toDate;
        } elseif (!$to) {
            $isValid = $currentDate >= $fromDate;
        } else {
            $isValid = $currentDate >= $fromDate && $currentDate <= $toDate;
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
}
