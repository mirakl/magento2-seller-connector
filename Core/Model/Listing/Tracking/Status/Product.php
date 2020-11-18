<?php
namespace MiraklSeller\Core\Model\Listing\Tracking\Status;

use Mirakl\MCI\Common\Domain\Product\ProductImportWithTransformationStatus;

class Product extends ProductImportWithTransformationStatus
{
    const EXPIRED = 'EXPIRED';

    /**
     * @var array
     */
    protected static $_statusLabels = [
        self::TRANSFORMATION_WAITING => 'Waiting for conversion',
        self::TRANSFORMATION_RUNNING => 'Transformation in progress',
        self::TRANSFORMATION_FAILED  => 'Conversion failed',
        self::TRANSFORMATION_QUEUED  => 'Transformation queued',
        self::WAITING                => 'Waiting for transmission',
        self::QUEUED                 => 'Transmission queued',
        self::RUNNING                => 'Transmission in progress',
        self::SENT                   => 'Sent for integration',
        self::FAILED                 => 'Integration failed',
        self::CANCELLED              => 'Integration cancelled',
        self::EXPIRED                => 'Integration expired',
        self::COMPLETE               => 'Integration complete',
    ];

    /**
     * @return  array
     */
    public static function getCompleteStatuses()
    {
        return [
            self::SENT,
            self::COMPLETE,
            self::CANCELLED,
            self::EXPIRED,
            self::FAILED,
        ];
    }

    /**
     * @return  array
     */
    public static function getErrorStatuses()
    {
        return [
            self::CANCELLED,
            self::FAILED,
        ];
    }

    /**
     * @return  array
     */
    public static function getFinalStatuses()
    {
        return [
            self::COMPLETE,
            self::CANCELLED,
            self::FAILED,
            self::EXPIRED,
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
    public static function isStatusError($status)
    {
        return in_array($status, self::getErrorStatuses());
    }

    /**
     * @param   string  $status
     * @return  bool
     */
    public static function isStatusComplete($status)
    {
        return in_array($status, self::getCompleteStatuses());
    }

    /**
     * @param   string  $status
     * @return  bool
     */
    public static function isStatusFinal($status)
    {
        return in_array($status, self::getFinalStatuses());
    }
}