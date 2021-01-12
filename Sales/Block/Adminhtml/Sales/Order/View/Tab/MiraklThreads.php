<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Sales\Order\View\Tab;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

class MiraklThreads extends TabWrapper implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return  string
     */
    public function getTabClass()
    {
        return 'mirakl_seller ajax only';
    }

    /**
     * @return  string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * @return  \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Mirakl Messages');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Mirakl Messages');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabUrl()
    {
        return $this->getUrl('mirakl_seller/thread/grid', ['_current' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->getOrder()->getMiraklOrderId();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
