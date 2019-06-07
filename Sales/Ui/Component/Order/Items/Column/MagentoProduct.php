<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class MagentoProduct extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param   ContextInterface    $context
     * @param   UiComponentFactory  $uiComponentFactory
     * @param   UrlInterface        $urlBuilder
     * @param   array               $components
     * @param   array               $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->urlBuilder = $urlBuilder;
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
                if ($productId = $item['product_id']) {
                    $productUrl = $this->urlBuilder->getUrl('catalog/product/edit', ['id' => $productId]);
                    $item[$fieldName] = sprintf('<a href="%s">%s</a>', $productUrl, $item['offer_sku']);
                } else {
                    $item[$fieldName] = sprintf('<span class="grid-severity-critical"><span>%s</span></span>', __('Not Found'));
                }
            }
        }

        return $dataSource;
    }
}