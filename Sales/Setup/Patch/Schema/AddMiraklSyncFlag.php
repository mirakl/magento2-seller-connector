<?php
declare(strict_types=1);

namespace MiraklSeller\Sales\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddMiraklSyncFlag implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param SalesSetupFactory        $salesSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->setup = $setup;
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $setup = $this->setup;
        $setup->startSetup();

        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
        $params = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'default'  => null
        ];

        $salesSetup->addAttribute('order', 'mirakl_sync', $params);

        $connection = $salesSetup->getConnection();
        $connection->update(
            $connection->getTableName('sales_order'),
            ['mirakl_sync' => 1],
            'mirakl_order_id IS NOT NULL'
        );

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [
            AddOrderAttributes::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}