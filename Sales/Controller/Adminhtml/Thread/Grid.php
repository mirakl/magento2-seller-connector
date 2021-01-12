<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

class Grid extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * Mirakl order threads grid
     *
     * @return  \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->_initOrder();

        return $this->resultLayoutFactory->create();
    }
}
