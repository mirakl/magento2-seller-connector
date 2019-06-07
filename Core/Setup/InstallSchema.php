<?php
namespace MiraklSeller\Core\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use MiraklSeller\Core\Model\Offer\State;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $defaultState = State::DEFAULT_STATE;

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_listing'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_listing'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true
            ], 'Listing Id')
            ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Marketplace Name')
            ->addColumn('connection_id', Table::TYPE_INTEGER, null, [
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0',
            ], 'Mirakl Connection ID')
            ->addColumn('is_active', Table::TYPE_BOOLEAN, null, ['default' => true, 'nullable' => false], 'Is Listing Active')
            ->addColumn('builder_model', Table::TYPE_TEXT, 255, ['default' => null], 'Builder Model')
            ->addColumn('builder_params', Table::TYPE_TEXT, '2M', ['default' => null], 'Builder Parameters')
            ->addColumn('product_id_type', Table::TYPE_TEXT, 255, ['nullable'  => true], 'Product Id Type')
            ->addColumn('product_id_value_attribute', Table::TYPE_TEXT, 255, ['default' => null], 'Product Id Value Attribute')
            ->addColumn('variants_attributes', Table::TYPE_TEXT, 255, ['default' => null], 'Variants Attributes')
            ->addColumn('last_export_date', Table::TYPE_DATETIME, null, ['default' => null, 'nullable' => true], 'Last Export Date')
            ->addColumn('offer_state', Table::TYPE_SMALLINT, 5, [
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => $defaultState,
            ],'Offer State')
            ->addColumn('offer_additional_fields_values', Table::TYPE_TEXT, '2M', ['nullable'  => false], 'Offer Additional Fields Values')
            ->addIndex($setup->getIdxName('mirakl_seller_listing', ['is_active']),
                ['is_active'])
            ->addIndex($setup->getIdxName('mirakl_seller_listing', ['connection_id']),
                ['connection_id'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_listing', 'connection_id', 'mirakl_seller_connection', 'id'),
                'connection_id',
                $setup->getTable('mirakl_seller_connection'),
                'id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Listing');
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_offer'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_offer'))
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Magento Offer Id')
            ->addColumn('listing_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => '0',], 'Mirakl Listing ID')
            ->addColumn('product_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => '0',], 'Magento Product ID')
            ->addColumn('product_import_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => true, 'default' => null], 'Product Import Id')
            ->addColumn('product_import_status', Table::TYPE_TEXT, 50, ['nullable' => true, 'default' => null], 'Product Import Status')
            ->addColumn('product_import_message', Table::TYPE_TEXT, '64k', ['nullable' => true, 'default' => null], 'Product Import Message')
            ->addColumn('offer_import_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => true, 'default' => null], 'Offer Import Id')
            ->addColumn('offer_import_status', Table::TYPE_TEXT, 50, ['nullable' => false], 'Offer Import Status')
            ->addColumn('offer_error_message', Table::TYPE_TEXT, '64k', ['nullable' => true, 'default' => null], 'Offer Error Message')
            ->addColumn('offer_hash', Table::TYPE_TEXT, 40, ['nullable' => false], 'Offer Hash')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Created Date')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Updated Date')
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['listing_id']), ['listing_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['product_id']), ['product_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['product_import_id']), ['product_import_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['product_import_status']), ['product_import_status'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['offer_import_id']), ['offer_import_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['offer_import_status']), ['offer_import_status'])
            ->addIndex($setup->getIdxName('mirakl_seller_offer', ['offer_hash']), ['offer_hash'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_offer', 'listing_id', 'mirakl_seller_listing', 'id'),
                'listing_id',
                $setup->getTable('mirakl_seller_listing'),
                'id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_offer', 'product_id', 'catalog_product_entity', 'entity_id'),
                'product_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Offer');
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_listing_tracking_product'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_listing_tracking_product'))
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Magento Id')
            ->addColumn('listing_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => '0',], 'Mirakl Listing ID')
            ->addColumn('import_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => true, 'default' => null], 'Import Id')
            ->addColumn('import_status', Table::TYPE_TEXT, 50, ['nullable' => true, 'default' => null], 'Import Status')
            ->addColumn('import_status_reason', Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => null], 'Import Status Reason')
            ->addColumn('transformation_error_report', Table::TYPE_TEXT, '4G', ['nullable' => true, 'default' => null], 'Transformation Error Report')
            ->addColumn('integration_error_report', Table::TYPE_TEXT, '4G', ['nullable' => true, 'default' => null], 'Integration Error Report')
            ->addColumn('integration_success_report', Table::TYPE_TEXT, '4G', ['nullable' => true, 'default' => null], 'Integration Success Report')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Created Date')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Updated Date')
            ->addIndex($setup->getIdxName('mirakl_seller_listing_tracking_product', ['listing_id']), ['listing_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_listing_tracking_product', ['import_status']), ['import_status'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_listing_tracking_product', 'listing_id', 'mirakl_seller_listing', 'id'),
                'listing_id',
                $setup->getTable('mirakl_seller_listing'),
                'id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Listing Tracking Product');
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_listing_tracking_offer'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_listing_tracking_offer'))
            ->addColumn('id', Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Magento Id')
            ->addColumn('listing_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => '0',], 'Mirakl Listing ID')
            ->addColumn('import_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => true, 'default' => null], 'Import Id')
            ->addColumn('import_status', Table::TYPE_TEXT, 50, ['nullable' => true, 'default' => null], 'Import Status')
            ->addColumn('error_report', Table::TYPE_TEXT, '4G', ['nullable' => true, 'default' => null], 'Error Report')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Created Date')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null], 'Updated Date')
            ->addIndex($setup->getIdxName('mirakl_seller_listing_tracking_offer', ['listing_id']), ['listing_id'])
            ->addIndex($setup->getIdxName('mirakl_seller_listing_tracking_offer', ['import_status']), ['import_status'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_listing_tracking_offer', 'listing_id', 'mirakl_seller_listing', 'id'),
                'listing_id',
                $setup->getTable('mirakl_seller_listing'),
                'id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Listing Tracking Offer');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
