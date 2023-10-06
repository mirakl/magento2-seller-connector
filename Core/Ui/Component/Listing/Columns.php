<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use MiraklSeller\Core\Helper\Data as CoreHelper;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @param ContextInterface       $context
     * @param ObjectManagerInterface $objectManager
     * @param CoreHelper             $coreHelper
     * @param array                  $components
     * @param array                  $data
     */
    public function __construct(
        ContextInterface $context,
        ObjectManagerInterface $objectManager,
        CoreHelper $coreHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->objectManager = $objectManager;
        $this->coreHelper = $coreHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        if ($this->coreHelper->isMsiEnabled() && !isset($this->components['salable_quantity'])) {
            $config = [
                'label'         => __('Salable Quantity'),
                'filter'        => false,
                'sortable'      => false,
                'sortOrder'     => 77,
                'component'     => 'Magento_InventorySalesAdminUi/js/product/grid/cell/salable-quantity',
                'dataType'      => 'text',
                'componentType' => 'column',
            ];

            $column = $this->objectManager->create('Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity', [
                'data' => [
                    'name'   => 'salable_quantity',
                    'config' => $config,
                ],
            ]);

            $this->addComponent('salable_quantity', $column);
        }
    }
}
