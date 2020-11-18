<?php
namespace MiraklSeller\Core\Model\Listing\Export;

use MiraklSeller\Core\Model\Listing;

interface ExportInterface
{
    /**
     * @param   Listing $listing
     * @return  array
     */
    public function export(Listing $listing);
}