<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Product\Fieldset;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Form\Fieldset;

class TabDisplay extends Fieldset
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param ContextInterface        $context
     * @param Registry                $coreRegistry
     * @param UiComponentInterface[]  $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        Registry $coreRegistry,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $listing = $this->coreRegistry->registry('mirakl_seller_listing');

        if (!$listing || !$listing->getId()) {
            $this->_data['config']['componentDisabled'] = true;
        } else {
            if ($this->getName() == 'product_content') {
                $this->_data['config']['label'] = sprintf(
                    '%s (%d)',
                    $this->_data['config']['label'],
                    count($listing->getProductIds())
                );
                $this->_data['config']['sub_section'] = __('Manage Exports');
            }
        }

        parent::prepare();
    }
}
