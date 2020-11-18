<?php
namespace MiraklSeller\Core\Observer\Product;

use Magento\Framework\Event\Observer;

class DeleteBeforeObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $this->deleteProducts([$product->getId()]);
    }
}