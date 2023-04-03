<?php
namespace MiraklSeller\Core\Model\Listing\Builder;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsBlock;
use Magento\Rule\Model\Condition\AbstractCondition;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Builder\Standard\Rule as ListingRule;
use MiraklSeller\Core\Model\Listing\Builder\Standard\RuleFactory as ListingRuleFactory;

class Standard implements BuilderInterface
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var ListingRuleFactory
     */
    protected $listingRuleFactory;

    /**
     * @var ListingRule[]
     */
    protected $listingRules = [];

    /**
     * @var Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var ConditionsBlock
     */
    protected $conditions;

    /**
     * @param   Registry            $registry
     * @param   FormFactory         $formFactory
     * @param   ListingRuleFactory  $listingRuleFactory,
     * @param   ConditionsBlock     $conditions
     * @param   Fieldset            $rendererFieldset
     */
    public function __construct(
        Registry $registry,
        FormFactory $formFactory,
        ListingRuleFactory $listingRuleFactory,
        ConditionsBlock $conditions,
        Fieldset $rendererFieldset
    ) {
        $this->coreRegistry = $registry;
        $this->formFactory = $formFactory;
        $this->listingRuleFactory = $listingRuleFactory;
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
    }

    /**
     * @param   Listing $listing
     * @return  ListingRule
     */
    public function getListingRule(Listing $listing)
    {
        if (!isset($this->listingRules[$listing->getId()])) {

            if ($listing instanceof ListingRule) {
                $this->listingRules[$listing->getId()] = $listing;
            } else {
                $rule = $this->listingRuleFactory->create();
                $rule->setData($listing->getData());
                $rule->setData('listing_object', $listing);
                $this->listingRules[$listing->getId()] = $rule;
            }
        }

        return $this->listingRules[$listing->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function build(Listing $listing)
    {
        $rule = $this->getListingRule($listing);

        return $rule->getMatchingProductIds();
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilderParams($data)
    {
        $key = 'conditions';

        // If tab not loaded, fallback on original values
        if (!isset($data[$key])) {
            return isset($data['builder_params']) ? $data['builder_params'] : [];
        }

        if (!is_array($data[$key])) {
            return [];
        }

        $arr = [];
        foreach ($data[$key] as $id => $value) {
            $path = explode('--', $id);
            $node = & $arr;
            for ($i = 0, $l = sizeof($path); $i < $l; $i++) {
                if (!isset($node[$key][$path[$i]])) {
                    $node[$key][$path[$i]] = [];
                }
                $node = & $node[$key][$path[$i]];
            }
            foreach ($value as $k => $v) {
                $node[$k] = $v;
            }
        }

        return $arr[$key][1];
    }

    /**
     * {@inheritdoc}
     */
    public function prepareForm(Form $block, &$data = [])
    {
        $listing = $this->coreRegistry->registry('mirakl_seller_listing');
        $model = $this->getListingRule($listing);

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->addTabToForm($block, $model);
        $block->setForm($form);
    }

    /**
     * @param   Form        $block
     * @param   ListingRule $model
     * @param   string      $fieldsetId
     * @param   string      $formName
     * @return  \Magento\Framework\Data\Form
     * @throws  \Magento\Framework\Exception\LocalizedException
     */
    protected function addTabToForm(
        Form $block,
        ListingRule $model,
        $fieldsetId = 'conditions_fieldset',
        $formName = 'mirakl_seller_listing_form'
    ) {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->formFactory->create();
        $form->setHtmlIdPrefix('listing_');

        $conditionsFieldSetId = $formName . '_conditions_fieldset_' . $model->getId();

        $newChildUrl = $block->getUrl(
            'mirakl_seller/listing/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => $formName]
        );

        $renderer = $this->rendererFieldset
            ->setNameInLayout('mirakl_fieldset_renderer')
            ->setTemplate('MiraklSeller_Core::listing/filter-products-fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId);

        $fieldset = $form->addFieldset(
            $fieldsetId,
            ['legend' => __('Filter Products To Export (don\'t add conditions if listing is applied to all products)')]
        )->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => true,
                'data-form-part' => $formName
            ]
        )
        ->setRule($model)
        ->setRenderer($this->conditions);

        $form->setValues($model->getData());
        $this->setConditionFormName($model->getConditions(), $formName, $conditionsFieldSetId);

        return $form;
    }

    /**
     * @param   AbstractCondition   $conditions
     * @param   string              $formName
     * @param   string              $jsFormName
     */
    private function setConditionFormName(AbstractCondition $conditions, $formName, $jsFormName)
    {
        $conditions->setFormName($formName);
        $conditions->setJsFormObject($jsFormName);

        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName, $jsFormName);
            }
        }
    }
}
