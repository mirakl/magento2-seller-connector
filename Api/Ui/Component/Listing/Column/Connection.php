<?php
namespace MiraklSeller\Api\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use MiraklSeller\Api\Model\Connection as ConectionModel;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory;

class Connection extends Column
{
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $connectionKey;

    /**
     * @param   ContextInterface    $context
     * @param   UiComponentFactory  $uiComponentFactory
     * @param   CollectionFactory   $collectionFactory
     * @param   Escaper             $escaper
     * @param   array               $components
     * @param   array               $data
     * @param   string              $connectionKey
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $collectionFactory,
        Escaper $escaper,
        array $components = [],
        array $data = [],
        $connectionKey = 'connection_id'
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->escaper = $escaper;
        $this->connectionKey = $connectionKey;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * @param   array   $item
     * @return  string
     */
    protected function prepareItem(array $item)
    {
        if (!empty($item[$this->connectionKey])) {
            $origConnections = $item[$this->connectionKey];
        }

        if (empty($origConnections)) {
            return '';
        }

        if (!is_array($origConnections)) {
            $origConnections = [$origConnections];
        }

        $data = $this->collectionFactory->create()->addIdFilter($origConnections);

        $content = [];
        foreach ($data as $connection) {
            /** @var ConectionModel $connection */
            $content[] = $connection->getName();
        }

        return implode(', ', $content);
    }
}
