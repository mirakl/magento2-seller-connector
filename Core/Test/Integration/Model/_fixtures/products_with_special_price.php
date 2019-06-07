<?php
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Customer\Model\GroupManagement;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->reinitialize();

/** @var ProductResource $productResource */
$productResource = Bootstrap::getObjectManager()
    ->create(ProductResource::class);
/** @var ProductFactory $productFactory */
$productFactory = Bootstrap::getObjectManager()
    ->create(ProductFactory::class);
$productId = $productResource->getIdBySku('MJ04-XS-Black');
$product = $productFactory->create();
$productResource->load($product, $productId);
$product->setStoreId(0);
$product->setPrice(45);
$product->setSpecialPrice(40);
$product->setSpecialFromDate('2018-03-01 00:00:00');
$product->setSpecialToDate('2028-03-01 00:00:00');
$product->setTierPrice([
    ['price_qty' => 5,  'price' => 36, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
    ['price_qty' => 10, 'price' => 32, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
    ['price_qty' => 20, 'price' => 25, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
]);
$product->setMediaGalleryEntries([]);
$productResource->save($product);

$productId = $productResource->getIdBySku('MJ04-XS-Blue');
$product = $productFactory->create();
$productResource->load($product, $productId);
$product->setStoreId(0);
$product->setVisibility(Visibility::VISIBILITY_BOTH);
$product->setPrice(35);
$product->setSpecialPrice(30);
$product->setSpecialFromDate('2018-02-01 00:00:00');
$product->setSpecialToDate('2028-02-01 00:00:00');
$product->setTierPrice([
    ['price_qty' => 5,  'price' => 33, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
    ['price_qty' => 10, 'price' => 32, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
]);
$product->setMediaGalleryEntries([]);
$productResource->save($product);

$productId = $productResource->getIdBySku('MJ04-XS-Purple');
$product = $productFactory->create();
$productResource->load($product, $productId);
$product->setStoreId(0);
$product->setSpecialPrice(44);
$product->setSpecialFromDate('2018-01-01 00:00:00');
$product->setSpecialToDate('2028-01-01 00:00:00');
$product->setTierPrice([
    ['price_qty' => 5,  'price' => 39, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
    ['price_qty' => 15, 'price' => 37, 'website_id' => 0, 'cust_group' => GroupManagement::CUST_GROUP_ALL,],
]);
$product->setMediaGalleryEntries([]);
$productResource->save($product);
