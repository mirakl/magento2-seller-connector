<?php

declare(strict_types=1);

namespace MiraklSeller\Sales\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddFulfillmentCenterAttribute implements SchemaPatchInterface
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
            'type'    => Table::TYPE_TEXT,
            'length'  => 100,
            'grid'    => true,
            'visible' => true
        ];
        $salesSetup->addAttribute('order', 'mirakl_fulfillment_center', $params);

        $setup->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}