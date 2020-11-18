<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use MiraklSeller\Core\Model\Listing;

class Conditions extends Generic implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Filter Products to Export');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Filter Products to Export');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        /** @var Listing $model */
        $model = $this->_coreRegistry->registry('mirakl_seller_listing');
        $model->getBuilder()->prepareForm($this);

        return parent::_prepareForm();
    }
}
