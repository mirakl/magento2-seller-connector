<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use MiraklSeller\Core\Model\Listing;

class OfferExport extends AbstractExport
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->_exportAction(Listing::TYPE_OFFER);
    }
}