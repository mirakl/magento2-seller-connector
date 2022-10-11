<?php
declare(strict_types=1);

namespace MiraklSeller\Sales\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddOrderAttributes implements SchemaPatchInterface
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
     * @inheritDoc
     */
    public function apply(): void
    {
        $setup = $this->setup;
        $setup->startSetup();

        $attributes = [
            'order' => [
                'mirakl_connection_id' => [
                    'type'     => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'grid'     => true,
                ],
                'mirakl_order_id' => [
                    'type'   => Table::TYPE_TEXT,
                    'length' => 255,
                ],
            ],
        ];

        $salesSetup = $this->getSalesSetup($setup);

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $salesSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return mixed
     */
    private function getSalesSetup(ModuleDataSetupInterface $setup)
    {
        return $this->salesSetupFactory->create(['setup' => $setup]);
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
