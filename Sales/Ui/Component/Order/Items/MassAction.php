<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use MiraklSeller\Sales\Ui\Component\DataProvider\ItemsDataProvider;

class MassAction extends \Magento\Ui\Component\MassAction
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
            $this->_data = []; // Empty mass action config if we cannot accept order lines
        }
    }
}