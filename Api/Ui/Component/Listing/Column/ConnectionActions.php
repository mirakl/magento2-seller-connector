<?php
namespace MiraklSeller\Api\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ConnectionActions extends Column
{
    /** Url path */
    const CONNECTION_URL_PATH_EDIT = 'mirakl_seller/connection/edit';
    const CONNECTION_URL_PATH_TEST = 'mirakl_seller/connection/test';
    const CONNECTION_URL_PATH_DELETE = 'mirakl_seller/connection/delete';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param   ContextInterface    $context
     * @param   UiComponentFactory  $uiComponentFactory
     * @param   UrlInterface        $urlBuilder
     * @param   Escaper             $escaper
     * @param   array               $components
     * @param   array               $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');

                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(self::CONNECTION_URL_PATH_EDIT, ['id' => $item['id']]),
                    'label' => __('Edit')
                ];
                $item[$name]['test'] = [
                    'href' => $this->urlBuilder->getUrl(self::CONNECTION_URL_PATH_TEST, ['id' => $item['id']]),
                    'label' => __('Test')
                ];

                $title = $this->escaper->escapeHtml($item['name']);
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(self::CONNECTION_URL_PATH_DELETE, ['id' => $item['id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete %1', $title),
                        'message' => __("Are you sure you want to delete this Mirakl '%1' connection?", $title)
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
