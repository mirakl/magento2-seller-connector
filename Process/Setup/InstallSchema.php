<?php
namespace MiraklSeller\Process\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->dropTable($setup->getTable('mirakl_seller_process'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_seller_process'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true
            ], 'Process Id')
            ->addColumn('parent_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true, 'nullable' => true, 'default' => null
            ], 'Parent Id')
            ->addColumn('type', Table::TYPE_TEXT, 100, ['nullable' => false], 'Type')
            ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Name')
            ->addColumn('status', Table::TYPE_TEXT, 50, ['nullable' => false, 'default'  => 'pending'], 'Status')
            ->addColumn('mirakl_status', Table::TYPE_TEXT, 50, ['default' => null], 'Mirakl Status')
            ->addColumn('synchro_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'default' => null], 'Synchro Id')
            ->addColumn('output', Table::TYPE_TEXT, '1g', ['default' => null], 'Output')
            ->addColumn('duration', Table::TYPE_INTEGER, null, ['unsigned' => true, 'default' => null], 'Duration')
            ->addColumn('file', Table::TYPE_TEXT, '64k', ['default' => null], 'File')
            ->addColumn('mirakl_file', Table::TYPE_TEXT, '64k', ['default' => null], 'Mirakl File')
            ->addColumn('helper', Table::TYPE_TEXT, 100, ['default' => null], 'Helper')
            ->addColumn('method', Table::TYPE_TEXT, 100, ['default' => null], 'Method')
            ->addColumn('params', Table::TYPE_TEXT, '2M', ['default' => null], 'Parameters')
            ->addColumn('hash', Table::TYPE_TEXT, 32, ['default' => null], 'Hash')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['default' => null], 'Created At')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['default' => null], 'Updated At')
            ->addIndex($setup->getIdxName('mirakl_process', ['type']), ['type'])
            ->addIndex($setup->getIdxName('mirakl_process', ['status']), ['status'])
            ->addIndex($setup->getIdxName('mirakl_process', ['mirakl_status']), ['mirakl_status'])
            ->addIndex($setup->getIdxName('mirakl_process', ['hash']), ['hash'])
            ->addForeignKey(
                $setup->getFkName('mirakl_seller_process', 'parent_id', 'mirakl_seller_process', 'id'),
                'parent_id',
                $setup->getTable('mirakl_seller_process'),
                'id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Processes');

        $setup->getConnection()->createTable($table);
        $setup->endSetup();
    }
}
