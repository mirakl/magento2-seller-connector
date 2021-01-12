<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

class View extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->_initOrder();

        return $this->resultLayoutFactory->create();
    }
}
