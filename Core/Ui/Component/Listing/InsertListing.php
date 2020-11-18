<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\AbstractComponent;

class InsertListing extends AbstractComponent
{
    const NAME = 'container';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param  ContextInterface         $context
     * @param  Registry                 $coreRegistry
     * @param  UiComponentInterface[]   $components
     * @param  array                    $data
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
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        $type = $this->getData('type');

        return static::NAME . ($type ? ('.' . $type) : '');
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        $listing = $this->coreRegistry->registry('mirakl_seller_listing');

        $config = $this->getData('config');
        $config['render_url'] = str_replace('%2A', $listing->getId(), $config['render_url']);
        $this->setData('config', $config);
    }
}