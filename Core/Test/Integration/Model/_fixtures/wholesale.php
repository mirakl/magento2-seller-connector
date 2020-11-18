<?php
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as StockItemResource;
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\CatalogInventory\Model\Stock\ItemFactory as StockItemFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->reinitialize();

/** @var StockItemFactory $stockItemFactory */
$stockItemFactory = Bootstrap::getObjectManager()->create(StockItemFactory::class);
/** @var StockItemResource $stockItemResource */
$stockItemResource = Bootstrap::getObjectManager()->get(StockItemResource::class);
/** @var ProductResource $productResource */
$productResource = Bootstrap::getObjectManager()->get(ProductResource::class);

/** @var StockItem $stockItem */
$stockItem = $stockItemFactory->create();
$productId = $productResource->getIdBySku('MJ01-XL-Orange');
$stockItemResource->loadByProductId($stockItem, $productId, 1);

$stockItem->setUseConfigMinSaleQty(0);
$stockItem->setMinSaleQty(3);
$stockItem->setUseConfigMaxSaleQty(0);
$stockItem->setMaxSaleQty(0);
$stockItem->setUseConfigEnableQtyInc(0);
$stockItem->setEnableQtyIncrements(1);
$stockItem->setUseConfigQtyIncrements(0);
$stockItem->setQtyIncrements(3);

$stockItemResource->save($stockItem);

/** @var StockItem $stockItem */
$stockItem = $stockItemFactory->create();
$productId = $productResource->getIdBySku('MJ01-XL-Red');
$stockItemResource->loadByProductId($stockItem, $productId, 1);

$stockItem->setUseConfigMinSaleQty(1);
$stockItem->setMinSaleQty(0);
$stockItem->setUseConfigMaxSaleQty(0);
$stockItem->setMaxSaleQty(0);
$stockItem->setUseConfigEnableQtyInc(0);
$stockItem->setEnableQtyIncrements(1);
$stockItem->setUseConfigQtyIncrements(0);
$stockItem->setQtyIncrements(3);

$stockItemResource->save($stockItem);

/** @var StockItem $stockItem */
$stockItem = $stockItemFactory->create();
$productId = $productResource->getIdBySku('MJ01-XL-Yellow');
$stockItemResource->loadByProductId($stockItem, $productId, 1);

$stockItem->setUseConfigMinSaleQty(0);
$stockItem->setMinSaleQty(3);
$stockItem->setUseConfigMaxSaleQty(1);
$stockItem->setMaxSaleQty(100);
$stockItem->setUseConfigEnableQtyInc(0);
$stockItem->setEnableQtyIncrements(1);
$stockItem->setUseConfigQtyIncrements(0);
$stockItem->setQtyIncrements(4);

$stockItemResource->save($stockItem);

/** @var StockItem $stockItem */
$stockItem = $stockItemFactory->create();
$productId = $productResource->getIdBySku('MJ01');
$stockItemResource->loadByProductId($stockItem, $productId, 1);

$stockItem->setUseConfigEnableQtyInc(0);
$stockItem->setEnableQtyIncrements(1);
$stockItem->setUseConfigQtyIncrements(0);
$stockItem->setQtyIncrements(4);

$stockItemResource->save($stockItem);
