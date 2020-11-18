<?php
namespace MiraklSeller\Process\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ProcessActions extends Column
{
    const PROCESS_URL_PATH_VIEW     = 'mirakl_seller/process/view';
    const PROCESS_URL_PATH_DELETE   = 'mirakl_seller/process/delete';

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

                $item[$name]['view'] = [
                    'href' => $this->urlBuilder->getUrl(self::PROCESS_URL_PATH_VIEW, ['id' => $item['id']]),
                    'label' => __('View')
                ];

                $title = $this->escaper->escapeHtml($item['name']);
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(self::PROCESS_URL_PATH_DELETE, ['id' => $item['id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete %1', $title),
                        'message' => __("Are you sure you want to delete this Mirakl '%1' process?", $title)
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
