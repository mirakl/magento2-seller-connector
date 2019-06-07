<?php
namespace MiraklSeller\Api\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use MiraklSeller\Api\Model\Connection;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_connection'));

        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_connection'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Connection Id')
            ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Connection Name')
            ->addColumn('api_url', Table::TYPE_TEXT, 255, ['nullable' => false], 'API URL')
            ->addColumn('api_key', Table::TYPE_TEXT, 255, ['nullable' => false], 'API Key')
            ->addColumn('store_id', Table::TYPE_SMALLINT, 5, [
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0',
            ], 'Magento Store ID')
            ->addColumn('shop_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => true, 'default' => null], 'Shop Id')
            ->addColumn('sku_code', Table::TYPE_TEXT, 255, ['nullable' => true], 'SKU Code')
            ->addColumn('errors_code', Table::TYPE_TEXT, 255, ['nullable' => true], 'Errors Code')
            ->addColumn('success_sku_code', Table::TYPE_TEXT, 255, ['nullable' => true], 'Success SKU Code')
            ->addColumn('messages_code', Table::TYPE_TEXT, 255, ['nullable' => true], 'Messages Code')
            ->addColumn('offer_additional_fields', Table::TYPE_TEXT, '2M', ['nullable' => false], 'Offer Additional Fields')
            ->addColumn('magento_tier_prices_apply_on', Table::TYPE_TEXT, '18', [
                'nullable' => false,
                'default' => Connection::VOLUME_PRICING
            ], 'Choose how you would like Magento tier prices to be exported')
            ->addColumn('exportable_attributes', Table::TYPE_TEXT, '2M', ['nullable' => false], 'Exportable Attributes (associated products)')
            ->addColumn('last_orders_synchronization_date', Table::TYPE_DATETIME, null, ['nullable' => true], 'Last Orders Synchronization Date')
            ->addIndex($setup->getIdxName('mirakl_seller/connection', ['store_id']),
                ['store_id'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_connection', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Seller Connections');

        $setup->getConnection()->createTable($table);
        $setup->endSetup();
    }
}
