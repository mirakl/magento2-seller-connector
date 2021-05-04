<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use MiraklSeller\Core\Helper\Data as CoreHelper;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var UiComponentFactory
     */
    protected $componentFactory;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $componentFactory
     * @param CoreHelper         $coreHelper
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $componentFactory,
        CoreHelper $coreHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->componentFactory = $componentFactory;
        $this->coreHelper = $coreHelper;
    }

    /**
     * {@inheridoc}
     */
    public function prepare()
    {
        parent::prepare();

        if ($this->coreHelper->isMsiEnabled() && !isset($this->components['salable_quantity'])) {
            $config = [
                'label'     => __('Salable Quantity'),
                'filter'    => false,
                'sortable'  => false,
                'sortOrder' => 77,
                'class'     => 'Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity',
                'component' => 'Magento_InventorySalesAdminUi/js/product/grid/cell/salable-quantity',
            ];
            $column = $this->componentFactory->create('salable_quantity', 'column', [
                'data' => ['config' => $config]
            ]);
            $column->prepare();
            $this->addComponent('salable_quantity', $column);
        }
    }
}
