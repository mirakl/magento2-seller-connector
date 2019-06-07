<?php
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->reinitialize();
$objectManager = Bootstrap::getObjectManager();

/** @var CategoryLinkManagementInterface $linkManagement */
$linkManagement = $objectManager->get(CategoryLinkManagementInterface::class);
$linkManagement->assignProductToCategories('MS01', [12, 16]);
$linkManagement->assignProductToCategories('MS01-M-Black', []);
$linkManagement->assignProductToCategories('MS01-M-Brown', []);