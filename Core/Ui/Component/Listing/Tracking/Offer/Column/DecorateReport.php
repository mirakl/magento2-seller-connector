<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Tracking\Offer\Column;

use MiraklSeller\Core\Ui\Component\Listing\Tracking\AbstractDecorateReport;

class DecorateReport extends AbstractDecorateReport
{
    /**
     * {@inheritdoc}
     */
    protected function getTrackingType()
    {
        return 'offer';
    }
}