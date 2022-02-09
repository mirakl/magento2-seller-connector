<?php
namespace MiraklSeller\Core\Ui\Component\DataProvider\Listing\Tracking;

use Magento\Framework\Api\Filter;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class Offer extends DataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getSearchResult()
    {
        /** @var \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $searchResult */
        $searchResult = parent::getSearchResult();

        $searchResult->getSelect()
            ->reset(Select::COLUMNS)
            ->columns([
                'id',
                'listing_id',
                'import_id',
                'import_status',
                'error_report' => new \Zend_Db_Expr('LENGTH(error_report) > 0'),
                'created_at',
                'updated_at',
            ]);

        return $searchResult;
    }

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