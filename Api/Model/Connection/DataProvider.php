<?php
namespace MiraklSeller\Api\Model\Connection;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ResourceModel\Connection\Collection;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory;

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
     * @var ShippingConfig
     */
    protected $shippingConfig;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param   string                  $name
     * @param   string                  $primaryFieldName
     * @param   string                  $requestFieldName
     * @param   CollectionFactory       $connectionCollectionFactory
     * @param   DataPersistorInterface  $dataPersistor
     * @param   ShippingConfig          $shippingConfig
     * @param   array                   $meta
     * @param   array                   $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $connectionCollectionFactory,
        DataPersistorInterface $dataPersistor,
        ShippingConfig $shippingConfig,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $connectionCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->shippingConfig = $shippingConfig;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($meta);
    }

    /**
     * @param   array   $meta
     * @return  array
     */
    public function prepareMeta(array $meta)
    {
        $meta = array_replace_recursive($meta, $this->prepareFieldsMeta(
            $this->getAttributesMeta()
        ));

        return $meta;
    }

    /**
     * @return  array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var Connection $connection */
        foreach ($items as $connection) {
            $this->loadedData[$connection->getId()] = $this->prepareData($connection->getData());
        }

        $data = $this->dataPersistor->get('mirakl_seller_connection');
        if (!empty($data)) {
            $connection = $this->collection->getNewEmptyItem();
            $connection->setData($data);
            $this->loadedData[$connection->getId()] = $this->prepareData($connection->getData());
            $this->dataPersistor->clear('mirakl_seller_connection');
        }

        return $this->loadedData;
    }

    /**
     * @param   array   $fieldValues
     * @return  array
     */
    private function prepareData($fieldValues)
    {
        if ($fieldValues['exportable_attributes'] && is_string($fieldValues['exportable_attributes'])) {
            $fieldValues['exportable_attributes'] = json_decode($fieldValues['exportable_attributes'], true);
        }

        $fieldValues['carriers_mapping'] = $this->getCarriersMapping($fieldValues['carriers_mapping'] ?? [], $fieldValues['store_id']);

        $result = [];
        foreach ($this->getFieldsMap() as $fieldSet => $fields) {
            foreach ($fields as $field) {
                if (isset($fieldValues[$field])) {
                    $result[$fieldSet][$field] = $fieldValues[$field];
                }
            }
        }

        return $result;
    }

    /**
     * @param   array   $fieldsMeta
     * @return  array
     */
    private function prepareFieldsMeta($fieldsMeta)
    {
        $result = [];
        foreach ($this->getFieldsMap() as $fieldSet => $fields) {
            foreach ($fields as $field) {
                $result[$fieldSet]['children'][$field]['arguments']['data']['config'] = $fieldsMeta;
            }
        }

        return $result;
    }

    /**
     * @return  array
     */
    protected function getFieldsMap()
    {
        return [
            'main' => [
                'id',
                'name',
                'api_url',
                'api_key',
                'store_id',
                'shop_id',
            ],
            'operator' => [
                'sku_code',
                'errors_code',
                'success_sku_code',
                'messages_code',
            ],
            'export' => [
                'magento_tier_prices_apply_on',
                'exportable_attributes',
                'exported_prices_attribute',
            ],
            'order' => [
                'carriers_mapping',
                'shipment_source_algorithm',
            ],
        ];
    }

    /**
     * @return  array
     */
    protected function getAttributesMeta()
    {
        return [
            'dataType'      => 'text',
            'formElement'   => 'input',
            'visible'       => '1',
            'sortOrder'     => 1,
            'componentType' => Field::NAME,
        ];
    }

    /**
     * @param   string|array    $originMapping
     * @param   int             $storeId
     * @return  array
     */
    private function getCarriersMapping($originMapping, $storeId = null)
    {
        if (empty($originMapping)) {
            $originMapping = [];
        } else if (is_string($originMapping)) {
            $originMapping = json_decode($originMapping, true);
        }

        $key = array_map(function($item) {
            return $item['magento_code'] ?? '';
        }, $originMapping);
        $originMapping = array_combine($key, $originMapping);

        $mapping = [];
        $carrierInstances = $this->shippingConfig->getAllCarriers($storeId);
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $mapping[] = [
                    'magento_code'   => $code,
                    'magento_label'  => $carrier->getConfigData('title'),
                    'mirakl_carrier' => $originMapping[$code]['mirakl_carrier'] ?? '',
                ];
            }
        }

        return $mapping;
    }
}
