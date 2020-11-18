<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

class OrderActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param   ContextInterface    $context
     * @param   UiComponentFactory  $uiComponentFactory
     * @param   UrlInterface        $urlBuilder
     * @param   ConnectionLoader    $connectionLoader
     * @param   OrderHelper         $orderHelper
     * @param   array               $components
     * @param   array               $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ConnectionLoader $connectionLoader,
        OrderHelper $orderHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->urlBuilder = $urlBuilder;
        $this->connectionLoader = $connectionLoader;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param   array   $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $connection = $this->connectionLoader->getCurrentConnection();
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $params = ['order_id' => $item['id'], 'connection_id' => $connection->getId()];

                // Add View link
                $item[$fieldName]['view'] = [
                    'href'   => $this->urlBuilder->getUrl('mirakl_seller/order/view', $params),
                    'label'  => __('View'),
                ];

                if (empty($item['magento_order_id']) && $this->orderHelper->canImport($item['status'])) {
                    // Add Import link
                    $item[$fieldName]['import'] = [
                        'href'    => $this->urlBuilder->getUrl('mirakl_seller/order/import', $params),
                        'label'   => __('Import'),
                        'confirm' => [
                            'title' => __('Import Mirakl Order #%1', $item['id']),
                            'message' => __('Are you sure you want to import this order in Magento?')
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
