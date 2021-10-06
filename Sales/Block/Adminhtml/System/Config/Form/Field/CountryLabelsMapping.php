<?php
namespace MiraklSeller\Sales\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\View\Element\Html\Select;

class CountryLabelsMapping extends AbstractFieldArray
{
    /**
     * @var Select
     */
    protected $_countryDropdownRenderer;

    /**
     * @return  Select
     */
    protected function getCountryDropdownRenderer()
    {
        if (!$this->_countryDropdownRenderer) {
            $this->_countryDropdownRenderer = $this->getLayout()->createBlock(CountryDropdown::class, '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_countryDropdownRenderer->setClass('admin__control-select');
        }

        return $this->_countryDropdownRenderer;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('country_label', [
            'label' => __('Mirakl Country')
        ]);

        $this->addColumn('country_id', [
            'label' => __('Magento Country'),
            'renderer' => $this->getCountryDropdownRenderer()
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $hash = $this->getCountryDropdownRenderer()->calcOptionHash($row->getData('country_id'));
        $row->setData('option_extra_attrs', ['option_' . $hash => 'selected="selected"']);
    }
}