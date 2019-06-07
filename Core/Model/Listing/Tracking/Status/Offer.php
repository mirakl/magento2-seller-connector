<?php
namespace MiraklSeller\Core\Model\Listing\Tracking\Status;

use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportStatus;

class Offer extends ImportStatus
{
    /**
     * @var array
     */
    protected static $_statusLabels = [
        self::WAITING                         => 'Waiting for import',
        self::WAITING_SYNCHRONIZATION_PRODUCT => 'Waiting for product integration',
        self::RUNNING                         => 'Import in progress',
        self::FAILED                          => 'Import failed',
        self::COMPLETE                        => 'Import complete',
    ];

    /**
     * @return  array
     */
    public static function getCompleteStatuses()
    {
        return [
            self::COMPLETE,
            self::FAILED,
        ];
    }

    /**
     * @return  array
     */
    public static function getStatuses()
    {
        return array_keys(self::getStatusLabels());
    }

    /**
     * @return  array
     */
    public static function getStatusLabels()
    {
        return self::$_statusLabels;
    }

    /**
     * @param   string  $status
     * @return  bool
     */
    public static function isStatusComplete($status)
    {
        return in_array($status, self::getCompleteStatuses());
    }
}