<?php
namespace MiraklSeller\Sales\Ui\Component\Sales\Order\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

class OrderSource extends Column
{
    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @param   ContextInterface        $context
     * @param   UiComponentFactory      $uiComponentFactory
     * @param   ConnectionLoader        $connectionLoader
     * @param   array                   $components
     * @param   array                   $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ConnectionLoader $connectionLoader,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->connectionLoader = $connectionLoader;
    }

    /**
     * @param   array   $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = $this->decorateSource($item);
            }
        }

        return $dataSource;
    }

    /**
     * Handles decoration of the Source column
     *
     * @param   array   $item
     * @return  string
     */
    public function decorateSource(array $item)
    {
        $class = 'magento';
        $label = __('Magento');

        if ($connectionId = $item['mirakl_connection_id']) {
            $class = 'marketplace';
            /** @var Connection $connection */
            $connection = $this->connectionLoader->getConnections()->getItemById($connectionId);
            $label = $connection ? $connection->getName() : __('Unknown Connection');
        }

        return sprintf('<span class="%s">%s</span>', $class, $label);
    }
}