<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Price extends Column
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceFormatter;

    /**
     * @param   ContextInterface        $context
     * @param   UiComponentFactory      $uiComponentFactory
     * @param   PriceCurrencyInterface  $priceFormatter
     * @param   array                   $components
     * @param   array                   $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->priceFormatter = $priceFormatter;
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
                $currencyCode = isset($item['currency_iso_code']) ? $item['currency_iso_code'] : null;
                $item[$fieldName] = $this->priceFormatter->format(
                    $item[$fieldName],
                    false,
                    null,
                    null,
                    $currencyCode
                );
            }
        }

        return $dataSource;
    }
}
