<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Product\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

class NbProducts extends Column
{
    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param OfferResourceFactory  $offerResourceFactory
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OfferResourceFactory $offerResourceFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->offerResourceFactory = $offerResourceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = count($this->offerResourceFactory->create()
                    ->getListingProductIds($item['id']));
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}