<?php
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->reinitialize();

/** @var ProductResource $productResource */
$productResource = Bootstrap::getObjectManager()
    ->create(ProductResource::class);
/** @var ProductFactory $productFactory */
$productFactory = Bootstrap::getObjectManager()
    ->create(ProductFactory::class);
$productId = $productResource->getIdBySku('MJ01-XL-Red');
$product = $productFactory->create();
$productResource->load($product, $productId);
$product->setStoreId(0);
$product->setData('msrp', 59.90);
$product->setMediaGalleryEntries([]);
$productResource->save($product);