<?php
namespace MiraklSeller\Sales\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements InstallDataInterface
{
    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param   SalesSetupFactory   $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
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
        $this->addOrderAttributes($setup, $attributes);

        $setup->endSetup();
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @param   array                       $attributes
     * @return  $this
     */
    private function addOrderAttributes(ModuleDataSetupInterface $setup, array $attributes)
    {
        $salesSetup = $this->getSalesSetup($setup);

        foreach ($attributes as $entityTypeId => $attrParams) {
            foreach ($attrParams as $code => $params) {
                $params['visible'] = false;
                $salesSetup->addAttribute($entityTypeId, $code, $params);
            }
        }

        return $this;
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  \Magento\Sales\Setup\SalesSetup
     */
    private function getSalesSetup(ModuleDataSetupInterface $setup)
    {
        return $this->salesSetupFactory->create(['setup' => $setup]);
    }
}
