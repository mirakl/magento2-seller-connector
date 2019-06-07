<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit\Tab;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

class TrackingProducts extends TabWrapper implements TabInterface
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->coreRegistry->registry('mirakl_seller_listing');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Track Products Exports');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabUrl()
    {
        return $this->getUrl('mirakl_seller/*/trackingProducts', ['_current' => true]);
    }
}
