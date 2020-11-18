<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class MagentoOrderLink extends \Magento\Ui\Component\Listing\Columns\Column
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
                if (empty($item['magento_order_id'])) {
                    $item[$fieldName] = '<em>' . __('Not imported') . '</em>';
                } else {
                    $url = $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $item['magento_order_id']]);
                    $item[$fieldName] = sprintf(
                        '<a href="%s" onclick="setLocation(this.href);">%s</a>',
                        $url,
                        $item['magento_increment_id']
                    );
                }
            }
        }

        return $dataSource;
    }
}