<?php
namespace MiraklSeller\Core\Model\Listing;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ResourceModel\Listing\Collection;
use MiraklSeller\Core\Model\ResourceModel\Listing\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string                  $name
     * @param string                  $primaryFieldName
     * @param string                  $requestFieldName
     * @param CollectionFactory       $pageCollectionFactory
     * @param DataPersistorInterface  $dataPersistor
     * @param Registry                $registry
     * @param array                   $meta
     * @param array                   $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $pageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $pageCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->coreRegistry = $registry;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var Listing $listing */
        $listing = $this->coreRegistry->registry('mirakl_seller_listing');
        if ($listing) {
            $this->loadedData[$listing->getId()] = $this->prepareData($listing);
        }

        $data = $this->dataPersistor->get('mirakl_seller_listing');
        if (!empty($data)) {
            $listing = $this->collection->getNewEmptyItem();
            $listing->setData($data);
            $this->loadedData[$listing->getId()] = $this->prepareData($listing);
            $this->dataPersistor->clear('mirakl_seller_listing');
        }

        return $this->loadedData;
    }

    /**
     * @param   Listing $listing
     * @return  array
     */
    private function prepareData(Listing $listing)
    {
        $fieldValues = $listing->getData();

        if (!empty($fieldValues['variants_attributes']) && is_string($fieldValues['variants_attributes'])) {
            $fieldValues['variants_attributes'] = json_decode($fieldValues['variants_attributes'], true);
        }

        if (!empty($fieldValues['builder_params']) && is_string($fieldValues['builder_params'])) {
            $fieldValues['builder_params'] = json_decode($fieldValues['builder_params'], true);
        }

        $additionalFieldsValues = [];
        if ($fieldValues['connection_id']) {
            foreach ($listing->getOfferAdditionalFields() as $additionalFields) {
                $code = $additionalFields['code'];
                $additionalFieldsValues[$code] = isset($fieldValues['offer_additional_fields_values'][$code]) ?
                    $fieldValues['offer_additional_fields_values'][$code] : ['default' => '', 'attribute' => ''];
            }
        }

        return [
            'main' => $fieldValues,
            'offer_additional_fields_values' => ['additional_fields' => $additionalFieldsValues]
        ];
    }
}
