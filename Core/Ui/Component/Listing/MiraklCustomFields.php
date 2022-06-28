<?php
namespace MiraklSeller\Core\Ui\Component\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form;
use MiraklSeller\Core\Model\Listing;

class MiraklCustomFields extends Form
{
    const MAX_TEXT_SIZE     = '2000';
    const MAX_TEXTAREA_SIZE = '5000';

    const NAME = 'miraklCustomFields';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   ContextInterface        $context
     * @param   FilterBuilder           $filterBuilder
     * @param   Registry                $registry
     * @param   UiComponentInterface[]  $components
     * @param   array                   $data
     */
    public function __construct(
        ContextInterface $context,
        FilterBuilder $filterBuilder,
        Registry $registry,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $filterBuilder, $components, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        /** @var Listing $listing */
        $listing = $this->coreRegistry->registry('mirakl_seller_listing');

        $additionalFieldComponent = $this->getComponent('additional_fields');

        foreach ($listing->getConnection()->getOfferAdditionalFields() as $additionalField) {
            $this->addComponent(
                $additionalField['code'],
                $this->createComponent($additionalFieldComponent, $additionalField)
            );
        }
    }

    /**
     * @param  UiComponentInterface $additionalFieldComponent
     * @param  array                $additionalField
     * @return UiComponentInterface
     */
    protected function createComponent($additionalFieldComponent, $additionalField)
    {
        $component = clone $additionalFieldComponent;
        $component->setName($additionalField['code']);

        $config = $component->getData('config');
        $config['marketplaceValue'] = [
            'label' => $additionalField['label'],
            'type' => $this->getTypeLabel($additionalField['type']),
            'required' => $additionalField['required'],
        ];
        $component->setData('config', $config);

        $this->transformChildComponent($component, $additionalField);

        return $component;
    }

    /**
     * @param string $type
     * @return string
     */
    private function getTypeLabel($type)
    {
        return __(ucfirst(strtolower(str_replace('_', ' ', $type))));
    }

    /**
     * @param  UiComponentInterface $component
     * @param  array                $additionalField
     */
    protected function transformChildComponent($component, $additionalField)
    {
        $default = clone $component->getComponent('default');
        $config = $default->getData('config');

        switch ($additionalField['type']) {
            case 'BOOLEAN':
                $config['component'] = 'Magento_Ui/js/form/element/single-checkbox';
                $config['formElement'] = 'checkbox';
                $config['prefer'] = 'toggle';
                $config['valueMap'] = ['false' => '0','true' => '1'];
                $config['dataType'] = 'boolean';

                $default->setData('type', 'form.checkbox');
                break;

            case 'DATE':
                $config['component'] = 'Magento_Ui/js/form/element/date';
                $config['formElement'] = 'date';
                $config['validation'] = ['validate-date' => true];

                $default->setData('type', 'form.date');
                break;

            case 'TEXTAREA':
                $config['component'] = 'Magento_Ui/js/form/element/textarea';
                $config['formElement'] = 'textarea';
                $config['tooltip'] = ['description' => __('Maximum %1 characters', self::MAX_TEXTAREA_SIZE)];

                $default->setData('type', 'form.textarea"');
                break;

            case 'LIST':
            case 'MULTIPLE_VALUES_LIST':
                if ($additionalField['type'] == 'LIST') {
                    $config['component'] = 'Magento_Ui/js/form/element/select';
                    $config['formElement'] = 'select';
                    $default->setData('type', 'form.select');
                } else {
                    $config['component'] = 'Magento_Ui/js/form/element/multiselect';
                    $config['formElement'] = 'multiselect';
                    $default->setData('type', 'form.multiselect');
                }

                $values = [['value' => '', 'label' => __('-- Please Select --')]];
                foreach ($additionalField['accepted_values'] as $value) {
                    $values[] = ['value' => $value, 'label' => $value];
                }
                $config['options'] = $values;
                break;

            case 'REGEX':
                if (!empty($additionalField['regex'])) {
                    $config['tooltip'] = ['description' => __(
                        'Must match the following format: %1', $additionalField['regex']
                    )];
                }
                break;

            case 'NUMERIC':
                $config['validation'] = ['validate-number' => true];
                $config['tooltip'] = ['description' => __('Must be a valid number')];
                break;

            case 'LINK':
                $config['validation'] = ['validate-url' => true];
                $config['tooltip'] = ['description' => __('Must be a valid URL')];
                break;

            case 'STRING':
            default:
                $config['tooltip'] = ['description' => __('Maximum %1 characters', self::MAX_TEXT_SIZE)];
        }

        if ($additionalField['required']) {
            if (!isset($config['validation'])) {
                $config['validation'] = [];
            }
            $config['validation']['required-entry'] = true;
        }

        $default->setData('config', $config);
        $component->addComponent('default', $default);
    }
}
