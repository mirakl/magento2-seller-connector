<?php
namespace MiraklSeller\Core\Observer\Product;

use Magento\Framework\Event\Observer;

class SaveAfterObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($product->isDisabled()) {
            $this->deleteProducts([$product->getId()]);
        }
    }
}