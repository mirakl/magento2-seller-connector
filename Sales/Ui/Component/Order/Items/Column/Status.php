<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use MiraklSeller\Sales\Helper\Data as SalesHelper;
use Mirakl\MMP\Common\Domain\Order\OrderState;

class Status extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var SalesHelper
     */
    protected $salesHelper;

    /**
     * @param   ContextInterface   $context
     * @param   UiComponentFactory $uiComponentFactory
     * @param   SalesHelper        $salesHelper
     * @param   array              $components
     * @param   array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SalesHelper $salesHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->salesHelper = $salesHelper;
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
                $item[$fieldName] = $this->getProductStatusHtml($item['status']);
            }
        }

        return $dataSource;
    }

    /**
     * @param   string  $status
     * @return  string
     */
    protected function getProductStatusHtml($status)
    {
        switch ($status) {
            case OrderState::CANCELED:
            case OrderState::REFUSED:
            case OrderState::REFUNDED:
                $class = 'grid-severity-critical';
                break;
            case OrderState::CLOSED:
            case OrderState::RECEIVED:
                $class = 'grid-severity-notice';
                break;
            default:
                $class = 'grid-severity-minor';
        }

        $statusList = $this->salesHelper->getOrderStatusList();
        $value = isset($statusList[$status]) ? $statusList[$status] : $status;

        return sprintf('<span class="%s"><span>%s</span></span>', $class, $value);
    }
}