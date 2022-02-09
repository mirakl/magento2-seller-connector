<?php

declare(strict_types=1);

namespace MiraklSeller\Sales\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;

class AddShipmentAttributesAndIndex implements DataPatchInterface
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

        $salesSetup = $this->getSalesSetup($setup);

        $attributes = [
            'shipment' => [
                'mirakl_shipment_id' => [
                    'type' => 'varchar',
                ],
            ],
        ];

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $salesSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        $salesSetup->getConnection()->addIndex(
            $setup->getTable('sales_shipment'),
            $salesSetup->getConnection()->getIndexName('sales_shipment', ['mirakl_shipment_id']),
            ['mirakl_shipment_id']
        );

        $setup->endSetup();
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  SalesSetup
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
