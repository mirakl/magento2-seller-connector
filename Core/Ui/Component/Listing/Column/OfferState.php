<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\ListingFactory;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;

class OfferState extends Column
{
    /**
     * @var ListingFactory
     */
    private $listingFactory;

    /**
     * @var ListingResource
     */
    private $listingResource;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ListingFactory     $listingFactory
     * @param ListingResource    $listingResource
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ListingFactory $listingFactory,
        ListingResource $listingResource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->listingFactory = $listingFactory;
        $this->listingResource = $listingResource;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $field = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $listing = $this->listingFactory->create();
                $this->listingResource->load($listing, $item[$item['id_field_name']]);
                $options = $listing->getOfferStates();
                if (isset($item[$field]) && isset($options[$item[$field]])) {
                    $item[$this->getData('name')] = $options[$item[$field]];
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}
