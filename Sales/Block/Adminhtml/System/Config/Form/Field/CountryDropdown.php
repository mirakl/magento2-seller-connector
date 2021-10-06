<?php
namespace MiraklSeller\Sales\Block\Adminhtml\System\Config\Form\Field;

use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Framework\View\Element\Context;

class CountryDropdown extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var CountryCollection
     */
    protected $_countryCollection;

    /**
     * @param   Context             $context
     * @param   CountryCollection   $countryCollection
     * @param   array               $data
     */
    public function __construct(
        Context $context,
        CountryCollection $countryCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_countryCollection = $countryCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * {@inheritdoc}
     */
    public function _toHtml()
    {
        if (!$this->_options) {
            $this->_options = $this->_countryCollection->loadData()->toOptionArray(false);
            array_unshift($this->_options, ['value' => '', 'label' => __('-- Please Select --')]);
        }

        $this->setExtraParams('style="max-width: 250px;"');

        return parent::_toHtml();
    }
}
