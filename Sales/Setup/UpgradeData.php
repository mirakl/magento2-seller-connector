<?php
namespace MiraklSeller\Sales\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
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
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addRefundAttributes($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param   ModuleDataSetupInterface    $setup
     * @return  $this
     */
    private function addRefundAttributes(ModuleDataSetupInterface $setup)
    {
        $salesSetup = $this->getSalesSetup($setup);

        $attributes = [
            'creditmemo' => [
                'mirakl_refund_id' => [
                    'type'     => Table::TYPE_INTEGER,
                    'unsigned' => true,
                ],
                'mirakl_refund_taxes' => [
                    'type'     => Table::TYPE_TEXT,
                ],
                'mirakl_refund_shipping_taxes' => [
                    'type'     => Table::TYPE_TEXT,
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
            $setup->getTable('sales_creditmemo'),
            $salesSetup->getConnection()->getIndexName('sales_creditmemo', ['mirakl_refund_id']),
            ['mirakl_refund_id']
        );

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
