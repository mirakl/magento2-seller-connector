<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Tracking\Product\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ListingActions extends Column
{
    const URL_PATH_UPDATE = 'mirakl_seller/tracking/update';

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
            $name = $this->getData('name');

            foreach ($dataSource['data']['items'] as & $item) {
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(
                        self::URL_PATH_UPDATE,
                        ['type' => 'product', 'id' => $item['id']]
                    ),
                    'label' => $this->escaper->escapeHtml(__('Update')),
                    'jsAction' => 'updateTracking',
                    'confirm' => __('Are you sure you want to update this Mirakl products export tracking?')
                ];
            }
        }

        return $dataSource;
    }
}
