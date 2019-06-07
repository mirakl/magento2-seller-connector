<?php
namespace MiraklSeller\Core\Model\Listing\Export\Formatter;

use MiraklSeller\Core\Model\Listing as Listing;

interface FormatterInterface
{
    /**
     * @param   array   $data
     * @param   Listing $listing
     * @return  array
     */
    public function format(array $data, Listing $listing);
}