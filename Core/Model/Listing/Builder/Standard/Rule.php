<?php
namespace MiraklSeller\Core\Model\Listing\Builder\Standard;

use Magento\CatalogRule\Model\Rule\Condition\CombineFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\ResourceModel\Iterator as ResourceIterator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @method  $this   setCollectedAttributes(array $attributes)
 * @method  array   getCollectedAttributes()
 */
class Rule extends \Magento\Framework\DataObject
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CombineFactory
     */
    protected $condCombineFactory;

    /**
     * @var \Magento\Rule\Model\Condition\Combine
     */
    protected $conditions;

    /**
     * @var ResourceIterator
     */
    protected $resourceIterator;

    /**
     * Form factory
     *
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    /**
     * Store rule form instance
     *
     * @var \Magento\Framework\Data\Form
     */
    protected $form;

    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $productIds;

    /**
     * @param   StoreManagerInterface       $storeManager
     * @param   ProductFactory              $productFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   CombineFactory              $condCombineFactory
     * @param   ResourceIterator            $resourceIterator
     * @param   FormFactory                 $formFactory
     * @param   array                       $data
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        ProductCollectionFactory $productCollectionFactory,
        CombineFactory $condCombineFactory,
        ResourceIterator $resourceIterator,
        FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($data);

        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->condCombineFactory = $condCombineFactory;
        $this->resourceIterator = $resourceIterator;
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        $listing = $this->getData('listing_object');
        if ($listing && method_exists($listing, $method)) {
            return call_user_func_array([$listing, $method], $args);
        }

        return parent::__call($method, $args);
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);

        $websites = $this->getWebsitesMap();
        $results = false;

        foreach ($websites as $websiteId => $defaultStoreId) {
            $product->setStoreId($defaultStoreId);
            $results = $results || $this->getConditions()->validate($product);
        }

        if ($results) {
            $this->productIds[] = $product->getId();
        }
    }

    /**
     * Retrieve rule combine conditions model
     *
     * @return \Magento\CatalogRule\Model\Rule\Condition\Combine
     */
    public function getConditions()
    {
        if ($this->conditions === null) {
            $this->conditions = $this->condCombineFactory->create();
            $this->conditions->setRule($this)->setId('1')->setPrefix('conditions');

            // Load rule conditions if it is applicable
            $conditions = $this->getBuilderParams();

            if (is_array($conditions) && !empty($conditions)) {
                $this->conditions->loadArray($conditions);
            }
        }

        return $this->conditions;
    }

    /**
     * Rule form getter
     *
     * @return \Magento\Framework\Data\Form
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create();
        }

        return $this->form;
    }

    /**
     * Get array of product ids which are matched by rule
     *
     * @return array
     */
    public function getMatchingProductIds()
    {
        if ($this->productIds === null) {
            $this->productIds = [];
            $this->setCollectedAttributes([]);

            /** @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addFieldToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

            if ($this->getStoreId()) {
                $productCollection->addWebsiteFilter($this->getWebsiteId());
            }

            $this->getConditions()->collectValidatedAttributes($productCollection);

            $this->resourceIterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackValidateProduct']],
                [
                    'attributes' => $this->getCollectedAttributes(),
                    'product' => $this->productFactory->create()
                ]
            );
        }

        return $this->productIds;
    }

    /**
     * Prepare website map
     *
     * @return array
     */
    protected function getWebsitesMap()
    {
        $map = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            // Continue if website has no store to be able to create catalog rule for website without store
            if ($website->getDefaultStore() === null) {
                continue;
            }
            $map[$website->getId()] = $website->getDefaultStore()->getId();
        }

        return $map;
    }
}
