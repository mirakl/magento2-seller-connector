<?php
namespace MiraklSeller\Api\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('mirakl_seller_connection'),
                'exported_prices_attribute',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'comment'  => 'Exported Prices Attribute',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('mirakl_seller_connection'),
                'carriers_mapping',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => '2M',
                    'nullable' => false,
                    'comment'  => 'Carriers Mapping',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('mirakl_seller_connection'),
                'shipment_source_algorithm',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'comment'  => 'Shipment Source Algorithm',
                ]
            );
        }
    }
}
