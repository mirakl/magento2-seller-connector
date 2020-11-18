<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\MassAction;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use MiraklSeller\Sales\Ui\Component\DataProvider\ItemsDataProvider;

class Column extends \Magento\Ui\Component\MassAction\Columns\Column
{
    /**
     * @return  ItemsDataProvider|DataProviderInterface
     */
    protected function getDataProvider()
    {
        return $this->context->getDataProvider();
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        if ($this->getDataProvider()->canMassAcceptOrderLines()) {
            parent::prepare();
        } else {
            $this->_data = []; // Empty mass action column if we cannot accept order lines
        }
    }
}