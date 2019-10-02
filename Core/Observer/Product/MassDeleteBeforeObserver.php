<?php
namespace MiraklSeller\Core\Observer\Product;

use Magento\Framework\Event\Observer;

class MassDeleteBeforeObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Backend\App\Action $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $action->getRequest();

        $productIds = $request->getParam('selected', []);

        if (!empty($productIds)) {
            $this->deleteProducts($productIds);
        }
    }
}