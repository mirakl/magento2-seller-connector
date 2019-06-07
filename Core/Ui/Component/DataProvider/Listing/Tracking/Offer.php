<?php
namespace MiraklSeller\Core\Ui\Component\DataProvider\Listing\Tracking;

use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class Offer extends DataProvider
{
    /**
     * {@inheritdoc}
     */
    protected function prepareUpdateUrl()
    {
        parent::prepareUpdateUrl();

        if ($listingId = $this->request->getParam('listing_id')) {
            $this->searchCriteriaBuilder->addFilter(new Filter([
                'field' => 'listing_id',
                'condition_type' => 'eq',
                'value' => $listingId,
            ]));
        }
    }
}