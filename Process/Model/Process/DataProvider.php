<?php
namespace MiraklSeller\Process\Model\Process;

use Magento\Backend\Block\Widget\Context;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MiraklSeller\Process\Model\ResourceModel\Process\Collection;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @param   string              $name
     * @param   string              $primaryFieldName
     * @param   string              $requestFieldName
     * @param   CollectionFactory   $pageCollectionFactory
     * @param   Context             $context
     * @param   array               $meta
     * @param   array               $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $pageCollectionFactory,
        Context $context,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $pageCollectionFactory->create();
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        foreach ($data['items'] as $id => $process) {
            $data['items'][$id]['id_field_name'] = 'id';
        }

        return $data;
    }
}
