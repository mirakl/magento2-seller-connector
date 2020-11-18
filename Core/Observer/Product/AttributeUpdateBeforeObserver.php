<?php
namespace MiraklSeller\Core\Observer\Product;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Event\Observer;

class AttributeUpdateBeforeObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getAttributesData();

        if (isset($data['status']) && $data['status'] === Status::STATUS_DISABLED) {
            $productIds = $observer->getEvent()->getProductIds();
            $this->deleteProducts($productIds);
        }
    }
}