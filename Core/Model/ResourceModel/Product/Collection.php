<?php
namespace MiraklSeller\Core\Model\ResourceModel\Product;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use MiraklSeller\Api\Model\Connection as MiraklConnection;
use MiraklSeller\Core\Helper\Config as ConfigHelper;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;

/**
 * /!\ This is not an override of the default Magento product collection but just an extension
 * in order to manipulate collection items as arrays instead of product objects for better performances.
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    // Avoid very large IN() clause in MySQL queries and use join instead on a temp table
    const MAX_PRODUCT_IDS_IN_WHERE = 1000;

    /**
     * @var bool
     */
    protected $_isEnterprise;

    /**
     * @var ListingHelper
     */
    protected $listingHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var array
     */
    protected $orderConditionFields = [
        'use_config_min_sale_qty'   => 'min_sale_qty',
        'use_config_max_sale_qty'   => 'max_sale_qty',
        'use_config_enable_qty_inc' => 'enable_qty_increments',
        'use_config_qty_increments' => 'qty_increments',
    ];

    /**
     * @var string[]
     */
    protected $multiSelectAttributes = [];

    /**
     * {@inheritdoc}
     */
    public function _construct()
    {
        parent::_construct();

        $this->listingHelper   = ObjectManager::getInstance()->get(ListingHelper::class);
        $this->configHelper    = ObjectManager::getInstance()->get(ConfigHelper::class);
        $this->productResource = ObjectManager::getInstance()->get(ProductResource::class);
        $this->visibility      = ObjectManager::getInstance()->get(Visibility::class);
        $this->_isEnterprise   = \MiraklSeller\Core\Helper\Data::isEnterprise();
    }

    /**
     * @param   Listing $listing
     * @return  $this
     */
    public function addAdditionalFieldsAttributes(Listing $listing)
    {
        $additionalFieldsValues = $listing->getOfferAdditionalFieldsValues();
        foreach ($additionalFieldsValues as $additionalFieldValue) {
            if (isset($additionalFieldValue['attribute']) && $additionalFieldValue['attribute']) {
                $this->addAttribute($additionalFieldValue['attribute']);
            }
        }

        return $this;
    }

    /**
     * @param   string  $attributeCode
     * @return  $this
     */
    public function addAttribute($attributeCode)
    {
        /** @var EavAttribute $attribute */
        $attribute = $this->_eavConfig->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeCode
        );
        if ($attribute) {
            if ($this->isAttributeUsingOptions($attribute)) {
                $this->addAttributeOptionValue($attribute);
            } else {
                $this->addAttributeToSelect($attributeCode);
            }
        }

        return $this;
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  $this
     */
    public function addAttributeOptionValue(EavAttribute $attribute)
    {
        if (!$this->isAttributeUsingOptions($attribute)) {
            return $this->addAttributeToSelect($attribute->getAttributeCode());
        }

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        $attributeCode = $attribute->getAttributeCode();
        $entityLinkColumn = $this->getEntity()->getLinkField();

        if ($attribute->getFrontendInput() == 'multiselect') {
            $this->multiSelectAttributes[] = $attributeCode;
        }

        $valueTable1 = $attributeCode . '_t1';
        $valueTable2 = $attributeCode . '_t2';

        $this->getSelect()
            ->joinLeft(
                [$valueTable1 => $attribute->getBackend()->getTable()],
                "e.{$entityLinkColumn} = {$valueTable1}.{$entityLinkColumn}"
                . " AND {$valueTable1}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTable1}.store_id = 0",
                []
            )
            ->joinLeft(
                [$valueTable2 => $attribute->getBackend()->getTable()],
                "e.{$entityLinkColumn} = {$valueTable2}.{$entityLinkColumn}"
                . " AND {$valueTable2}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTable2}.store_id = {$storeId}",
                []
            );

        $valueExpr = $this->_conn->getCheckSql(
            "{$valueTable2}.value_id > 0",
            "{$valueTable2}.value",
            "{$valueTable1}.value"
        );

        $optionTable1   = $attributeCode . '_option_value_t1';
        $optionTable2   = $attributeCode . '_option_value_t2';
        $tableJoinCond1 = "FIND_IN_SET({$optionTable1}.option_id, {$valueExpr}) AND {$optionTable1}.store_id = 0";
        $tableJoinCond2 = "FIND_IN_SET({$optionTable2}.option_id, {$valueExpr}) AND {$optionTable2}.store_id = {$storeId}";
        $valueExpr      = $this->_conn->getCheckSql("{$optionTable2}.value_id IS NULL",
            "{$optionTable1}.value",
            "{$optionTable2}.value"
        );

        $this->getSelect()
            ->joinLeft(
                [$optionTable1 => $this->getTable('eav_attribute_option_value')],
                $tableJoinCond1,
                []
            )
            ->joinLeft(
                [$optionTable2 => $this->getTable('eav_attribute_option_value')],
                $tableJoinCond2,
                [$attributeCode => $valueExpr]
            );

        return $this;
    }

    /**
     * Add category ids to loaded items
     *
     * @param   bool    $fallbackToParent
     * @return  $this
     */
    public function addCategoryIds($fallbackToParent = true)
    {
        if ($this->getFlag('category_ids_added')) {
            return $this;
        }

        $productIds = array_keys($this->_items);
        if (empty($productIds)) {
            return $this;
        }

        $productCategoryIds = $this->getProductCategoryIds($productIds);

        $productsWithCategories = [];
        foreach ($productCategoryIds as $productId => $categoryIds) {
            $productsWithCategories[$productId] = true;
            $this->_items[$productId]['category_ids'] = $categoryIds;
        }

        if ($fallbackToParent) {
            // Search for categories associated to parent product if possible
            $productsWithoutCategories = array_diff_key($this->_items, $productsWithCategories);
            $parentProductIds = $this->getParentProductIds(array_keys($productsWithoutCategories));
            if (!empty($parentProductIds)) {
                $parentIds = [];
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }
                $parentIds = array_unique($parentIds);
                $parentProductCategoryIds = $this->getProductCategoryIds($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductCategoryIds[$parentId])) {
                            $this->_items[$productId]['category_ids'] = $parentProductCategoryIds[$parentId];
                            continue 2; // skip this product as soon as we have found some categories for it
                        }
                    }
                }
            }
        }

        $this->setFlag('category_ids_added', true);

        return $this;
    }

    /**
     * Add category names to loaded items
     *
     * @return  $this
     */
    public function addCategoryNames()
    {
        if ($this->getFlag('category_names_added') || empty($this->_items)) {
            return $this;
        }

        $productIds = array_keys($this->_items);

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        /** @var EavAttribute $attribute */
        $attribute = $this->_eavConfig->getAttribute('catalog_category', 'name');

        $colsExprSql = [
            'product_id',
            'name' => $this->_conn->getIfNullSql('category_name_t2.value', 'category_name_t1.value')
        ];
        $select = $this->_conn
            ->select()
            ->from(['category_product' =>  $this->_productCategoryTable], $colsExprSql)
            ->joinLeft(
                ['category_name_t1' => $attribute->getBackend()->getTable()],
                "category_product.category_id = category_name_t1.entity_id"
                . " AND category_name_t1.attribute_id = {$attribute->getId()}"
                . " AND category_name_t1.store_id = 0",
                []
            )
            ->joinLeft(
                ['category_name_t2' => $attribute->getBackend()->getTable()],
                "category_product.category_id = category_name_t2.entity_id"
                . " AND category_name_t2.attribute_id = {$attribute->getId()}"
                . " AND category_name_t2.store_id = {$storeId}",
                []
            )
            ->where('category_product.product_id IN (?)', $productIds);

        $data = $this->_conn->fetchAll($select);

        foreach ($data as $info) {
            $productId = $info['product_id'];
            if (!isset($this->_items[$productId]['category_names'])) {
                $this->_items[$productId]['category_names'] = [];
            }
            if (null !== $info['name']) {
                $this->_items[$productId]['category_names'][] = $info['name'];
            }
        }

        $this->setFlag('category_names_added', true);

        return $this;
    }

    /**
     * Add category paths to loaded items
     *
     * @return  $this
     */
    public function addCategoryPaths()
    {
        if ($this->getFlag('category_paths_added') || empty($this->_items)) {
            return $this;
        }

        // Category ids are required
        $this->addCategoryIds();

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        /** @var EavAttribute $attribute */
        $attribute = $this->_eavConfig->getAttribute('catalog_category', 'name');

        $entityIdColumn = $this->getEntity()->getEntityIdField();
        $entityLinkColumn = $this->getEntity()->getLinkField();

        $colsExprSql = [
            'category_id' => "categories.$entityIdColumn",
            'path' => 'categories.path',
            'name' => $this->_conn->getIfNullSql('category_name_t2.value', 'category_name_t1.value')
        ];
        $select = $this->_conn
            ->select()
            ->from(['categories' => $this->getTable('catalog_category_entity')], $colsExprSql)
            ->joinLeft(
                ['category_name_t1' => $attribute->getBackend()->getTable()],
                "categories.$entityLinkColumn = category_name_t1.$entityLinkColumn"
                . " AND category_name_t1.attribute_id = {$attribute->getId()}"
                . " AND category_name_t1.store_id = 0",
                []
            )
            ->joinLeft(
                ['category_name_t2' => $attribute->getBackend()->getTable()],
                "categories.$entityLinkColumn = category_name_t2.$entityLinkColumn"
                . " AND category_name_t2.attribute_id = {$attribute->getId()}"
                . " AND category_name_t2.store_id = {$storeId}",
                []
            );

        $categories = $this->_conn->fetchAssoc($select);

        $getCategoryPath = function ($categoryId) use ($categories) {
            $pathNames = [];
            if (isset($categories[$categoryId])) {
                $pathCategoryIds = explode('/', $categories[$categoryId]['path']);
                foreach ($pathCategoryIds as $pathCategoryId) {
                    if ($pathCategoryId > 1 && isset($categories[$pathCategoryId])) {
                        $pathNames[] = $categories[$pathCategoryId]['name'];
                    }
                }
            }

            return $pathNames;
        };

        foreach ($this->_items as $productId => $data) {
            $this->_items[$productId]['category_paths'] = [];
            if (!isset($data['category_ids'])) {
                $this->_items[$productId]['category_ids'] = [];
            } else {
                foreach ($data['category_ids'] as $categoryId) {
                    $this->_items[$productId]['category_paths'][$categoryId] = $getCategoryPath($categoryId);
                }
            }
        }

        $this->setFlag('category_paths_added', true);

        return $this;
    }

    /**
     * @param   string  $field
     * @param   string  $alias
     * @return  $this
     */
    public function addFieldToSelect($field, $alias = null)
    {
        $this->getSelect()->columns($field);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addIdFilter($productId, $exclude = false)
    {
        if (!is_array($productId) || count($productId) < static::MAX_PRODUCT_IDS_IN_WHERE) {
            return parent::addIdFilter($productId, $exclude);
        }

        // Handle large product ids data in a temporary table to avoid big IN() clause that is slow
        $tmpTableName = $this->_createTempTableWithProductIds($productId);
        $this->getSelect()->join(
            ['tmp_products' => $this->getTable($tmpTableName)],
            self::MAIN_TABLE_ALIAS . '.entity_id = tmp_products.product_id',
            ''
        );

        return $this;
    }

    /**
     * @param   Listing $listing
     * @return  $this
     */
    public function addListingPriceData(Listing $listing)
    {
        $this->listingHelper->addListingPriceDataToCollection($listing, $this);

        return $this;
    }

    /**
     * Add image URL to loaded items
     *
     * @param   int $nbImage
     * @return  $this
     */
    public function addMediaGalleryAttribute($nbImage = 1)
    {
        $productIds = array_keys($this->_items);

        if (empty($productIds) || $this->getFlag('images_url_added')) {
            return $this;
        }

        // Retrieve products images
        $productImagesDefault = $this->getProductImages($productIds);

        if (!$storeId = $this->getStoreId()) {
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        $productImagesStore = $this->getProductImages($productIds, $storeId);

        // Retrieve parent product images for products without image associated
        $productsWithoutImages = array_diff_key($this->_items, $productImagesDefault);
        if (!empty($productsWithoutImages)) {
            $parentProductIds = $this->getParentProductIds(array_keys($productsWithoutImages));
            if (!empty($parentProductIds)) {
                $parentIds = [];
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }
                $parentIds = array_unique($parentIds);
                $parentProductImages = $this->getProductImages($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductImages[$parentId])) {
                            $productImagesDefault[$productId] = $parentProductImages[$parentId];
                            continue 2; // skip this product as soon as we have found some images for it
                        }
                    }
                }
            }
        }

        foreach ($productImagesDefault as $productId => $images) {
            if (!empty($productImagesStore[$productId])) {
                // Override default images by store view images if available
                $images = $productImagesStore[$productId];
            }
            foreach ($images as $i => $image) {
                if ($nbImage <= $i) {
                    break;
                }
                $imageKey = \MiraklSeller\Core\Model\Listing\Export\Formatter\Product::IMAGE_FIELD . ($i + 1);
                $this->_items[$productId][$imageKey] = $this->getMediaUrl($image['file']);
            }
        }
        unset($productImagesDefault);
        unset($productImagesStore);

        $this->setFlag('images_url_added', true);

        return $this;
    }

    /**
     * @param   int     $websiteId
     * @param   int     $groupId
     * @param   string  $tierPricesApplyOn
     * @return  $this
     */
    public function addTierPricesToSelect(
        $websiteId,
        $groupId = CustomerGroup::NOT_LOGGED_IN_ID,
        $tierPricesApplyOn = MiraklConnection::VOLUME_PRICING
    ) {
        if ($this->getFlag('tier_prices_added')) {
            return $this;
        }

        // value field is set to 0 by Magento when a volume discount is applied
        $valueField = $tierPricesApplyOn === MiraklConnection::VOLUME_DISCOUNTS ? 'percentage_value' : 'value';

        $entityLinkColumn = $this->getEntity()->getLinkField();
        $tierPricesSql = new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT_WS('|', FLOOR(tier_prices.qty), ROUND(tier_prices.$valueField, 2)) SEPARATOR ',')");
        $this->getSelect()
            ->joinLeft(
                ['tier_prices' => $this->getTable('catalog_product_entity_tier_price')],
                sprintf(
                    "e.$entityLinkColumn = tier_prices.$entityLinkColumn AND (tier_prices.website_id = %d OR tier_prices.website_id = 0) AND (customer_group_id = %d OR all_groups = 1)",
                    $websiteId,
                    $groupId
                ),
                ['tier_prices' => $tierPricesSql]
            )
            ->group('e.entity_id');

        $this->setFlag('tier_prices_added', true);

        return $this;
    }

    /**
     * @return  $this
     */
    public function addQuantityToSelect()
    {
        if ($this->getFlag('qty_added')) {
            return $this;
        }

        $this->joinTable(
            'cataloginventory_stock_item',
            'product_id = entity_id',
            array_merge(
                ['qty', 'manage_stock', 'use_config_manage_stock'],
                array_keys($this->orderConditionFields),
                array_values($this->orderConditionFields)
            ),
            '{{table}}.stock_id = 1',
            'left'
        );

        $this->setFlag('qty_added', true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getLoadAttributesSelect($table, $attributeIds = [])
    {
        if (count($this->_itemsById) < static::MAX_PRODUCT_IDS_IN_WHERE) {
            return parent::_getLoadAttributesSelect($table, $attributeIds);
        }

        if (empty($attributeIds)) {
            $attributeIds = $this->_selectAttributes;
        }
        $storeId = $this->getStoreId();
        $connection = $this->getConnection();

        $entityTable = $this->getEntity()->getEntityTable();
        $indexList = $connection->getIndexList($entityTable);
        $entityIdField = $indexList[$connection->getPrimaryKeyName($entityTable)]['COLUMNS_LIST'][0];
        $tmpTableName = $this->_createTempTableWithProductIds(array_keys($this->_itemsById));

        if ($storeId) {
            $joinCondition = [
                't_s.attribute_id = t_d.attribute_id',
                "t_s.{$entityIdField} = t_d.{$entityIdField}",
                $connection->quoteInto('t_s.store_id = ?', $storeId),
            ];

            $select = $connection->select()->from(
                ['t_d' => $table],
                ['attribute_id']
            )->join(
                ['e' => $entityTable],
                "e.{$entityIdField} = t_d.{$entityIdField}",
                ['e.entity_id']
            )->where(
                't_d.attribute_id IN (?)',
                $attributeIds
            )->joinLeft(
                ['t_s' => $table],
                implode(' AND ', $joinCondition),
                []
            )->where(
                't_d.store_id = ?',
                $connection->getIfNullSql('t_s.store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            )->join(
                $this->getTable($tmpTableName),
                "e.entity_id = product_id",
                ''
            );
        } else {
            $select = $connection->select()->from(
                ['t_d' => $table],
                ['attribute_id']
            )->join(
                ['e' => $entityTable],
                "e.{$entityIdField} = t_d.{$entityIdField}",
                ['e.entity_id']
            )->where(
                'attribute_id IN (?)',
                $attributeIds
            )->where(
                'store_id = ?',
                $this->getDefaultStoreId()
            )->join(
                $this->getTable($tmpTableName),
                "e.entity_id = product_id",
                ''
            );
        }

        return $select;
    }

    /**
     * @param   array   $productIds
     * @return  string
     */
    protected function _createTempTableWithProductIds(array $productIds)
    {
        \Magento\Framework\Profiler::start(__METHOD__);

        // Create an unique temporary table name
        $tmpTableName = 'tmp_mirakl_seller_products_' . uniqid();

        // Temporary table definition
        $tmpTable = $this->_conn
            ->newTable($this->getTable($tmpTableName))
            ->addColumn('product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
                'unsigned' => true, 'nullable' => false, 'default' => '0'
            ]);

        // Create the temporary table
        $this->_conn->createTemporaryTable($tmpTable);

        // Insert all product ids in the temporary table
        $this->_conn->insertArray($this->getTable($tmpTableName), ['product_id'], $productIds);

        \Magento\Framework\Profiler::stop(__METHOD__);

        return $tmpTableName;
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function getMediaUrl($file)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        $file = ltrim(str_replace('\\', '/', $file), '/');

        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . $file;
    }

    /**
     * Returns parent ids of specified product ids
     *
     * @param   array   $productIds
     * @return  array
     */
    protected function getParentProductIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $select = $this->_conn->select()
            ->from(['cpsl' => $this->getTable('catalog_product_super_link')],
                   ['product_id', 'parent_id']);

        if ($this->_isEnterprise) {
            // In M2 Enterprise, row_id is used as parent_id to link products
            // We need to join results to catalog_product_entity table to fetch real products entity ids
            $select->joinLeft(['cpe' => $this->getTable('catalog_product_entity')],
                'cpsl.parent_id = cpe.row_id',
                ['parent_id' => 'cpe.entity_id']
            );
        }

        $select->where('cpsl.product_id IN (?)', $productIds);

        $parentIds = array_fill_keys($productIds, []);
        foreach ($this->_conn->fetchAll($select) as $row) {
            $productId = $row['product_id'];
            $parentIds[$productId][] = (int) $row['parent_id'];
        }

        return $parentIds;
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductCategoryIds(array $productIds)
    {
        $select = $this->_conn
            ->select()
            ->from($this->_productCategoryTable, ['product_id', 'category_id'])
            ->where('product_id IN (?)', $productIds);

        $categoryIds = [];

        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            $productId = $row['product_id'];
            if (!isset($categoryIds[$productId])) {
                $categoryIds[$productId] = [];
            }
            if (null !== $row['category_id']) {
                $categoryIds[$productId][] = (int) $row['category_id'];
            }
        }
        unset($stmt);

        return $categoryIds;
    }

    /**
     * @param   array   $productIds
     * @param   int     $storeId
     * @return  array
     */
    public function getProductImages(array $productIds, $storeId = 0)
    {
        if (empty($productIds)) {
            return [];
        }

        $attribute = $this->getAttribute('image');
        $attributeId = $attribute ? $attribute->getId() : null;

        $entityLinkColumn = $this->getEntity()->getLinkField();

        $select = $this->_conn->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity')], 'entity_id')
            ->joinLeft(
                ['mgv' => $this->getTable('catalog_product_entity_media_gallery_value')],
                "(mgv.$entityLinkColumn = cpe.$entityLinkColumn AND mgv.store_id = $storeId AND mgv.disabled = 0)",
                ['label', 'position']
            )
            ->joinLeft(
                ['mg1' => $this->getTable('catalog_product_entity_media_gallery')],
                'mg1.value_id = mgv.value_id',
                ['file' => 'value']
            )
            ->joinLeft(
                ['mgvbi' => $this->getTable('catalog_product_entity_varchar')],
                "(mgvbi.$entityLinkColumn = cpe.$entityLinkColumn AND mg1.value = mgvbi.value AND " .
                "mgvbi.store_id = $storeId AND mgvbi.attribute_id = $attributeId)",
                []
            )
            ->where('cpe.entity_id IN (?)', $productIds);

        if ($storeId) {
            $select->where('mg1.value IS NOT NULL');
        }

        $select->order(['entity_id ASC', 'position ASC', 'file ASC']);

        $images = [];
        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            if (empty($row['file'])) {
                continue;
            }
            $productId = $row['entity_id'];
            if (!isset($images[$productId])) {
                $images[$productId] = [];
            }
            $images[$productId][] = $row;
        }
        unset($stmt);

        return $images;
    }

    /**
     * Checks if specified attribute is using options or not
     *
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(EavAttribute $attribute)
    {
        $model = $attribute->getSource();
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            ($backend == 'int' && $model instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) ||
            (($backend == 'varchar' || $backend == 'text') && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * {@inheritdoc}
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_renderFilters();
        $this->_renderOrders();

        $this->_loadEntities($printQuery, $logQuery);
        $this->_loadAttributes($printQuery, $logQuery);

        $this->_setIsLoaded();

        return $this;
    }

    /**
     * @param   array   $productAttributes
     * @param   array   $attrCodes
     * @param   bool    $orderCondition
     * @return  $this
     */
    public function overrideByParentData(
        $productAttributes = [],
        $attrCodes = [],
        $orderCondition = false
    ) {
        if ($this->getFlag('parent_data_override') || empty($this->_items)) {
            return $this;
        }

        $productIds = array_keys($this->_items);

        /** @var Collection $collection */
        $collection = $this->_entityFactory->create(self::class);
        if (count($productAttributes)) {
            $collection->addFieldToSelect($productAttributes);
        }

        foreach ($attrCodes as $attrCode) {
            $collection->addAttribute($attrCode);
        }

        if ($orderCondition) {
            $collection->joinTable(
                'cataloginventory_stock_item',
                'product_id = entity_id',
                array_merge(
                    array_keys($this->orderConditionFields),
                    array_values($this->orderConditionFields)
                ),
                '{{table}}.stock_id = 1',
                'left'
            );
        }

        $this->_linkToChildren($productIds, $collection->getSelect());

        foreach ($collection as $data) {
            $parentId = $data['entity_id'];

            // Remove useless attribute from catalog_product_entity base table
            $fields = $this->productResource->getProductBaseColumns();
            foreach (array_diff($fields, array_values($productAttributes)) as $field) {
                unset($data[$field]);
            }

            $data['parent_id'] = $parentId;

            $entityIds = explode(',', $data['entity_ids']);
            unset($data['entity_ids']);

            if ($orderCondition) {
                // Do not override order condition data if config is used
                foreach ($this->orderConditionFields as $useConfig => $value) {
                    if ($data[$useConfig]) {
                        unset($data[$useConfig], $data[$value]);
                    }
                }
            }

            foreach ($entityIds as $entityId) {
                // If product have multiple parent, keep data from the first
                if (isset($this->_items[$entityId]['parent_id'])) {
                    continue;
                }

                $this->_items[$entityId] = array_merge($this->_items[$entityId], $data);
            }
        }

        $this->setFlag('parent_data_override', true);

        return $this;
    }

    /**
     * @param   array   $childrenIds
     * @param   Select  $select
     * @return  $this
     */
    protected function _linkToChildren($childrenIds, $select = null)
    {
        if (!$select) {
            $select = $this->getSelect();
        }

        $storeId = $this->getStoreId();

        $entityIdColumn = $this->getEntity()->getEntityIdField();
        $entityLinkColumn = $this->getEntity()->getLinkField();
        $linkColumn = $this->_isEnterprise ? "child.$entityLinkColumn" : 'link.product_id';

        $visibilityAttribute = $this->_eavConfig->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'visibility'
        );
        $visibilityId = $visibilityAttribute ? $visibilityAttribute->getId() : null;
        $visibilityValues = implode(',', $this->visibility->getVisibleInCatalogIds());

        $childIdsSql = new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT link.product_id SEPARATOR ',')");

        $select->joinLeft(
            ['link' => $this->getTable('catalog_product_super_link')],
            "link.parent_id = e.$entityLinkColumn",
            ['entity_ids' => $childIdsSql]
        );

        if ($this->_isEnterprise) {
            $select->joinLeft(
                ['child' => $this->getTable('catalog_product_entity')],
                "link.product_id = child.$entityIdColumn",
                []
            );
        }

        $select->joinLeft(
                ['visibiliy_store' => $this->getTable('catalog_product_entity_int')],
                "visibiliy_store.$entityLinkColumn = $linkColumn AND " .
                "visibiliy_store.attribute_id = $visibilityId AND visibiliy_store.store_id = $storeId",
                []
            )
            ->joinLeft(
                ['default_visibiliy_store' => $this->getTable('catalog_product_entity_int')],
                "default_visibiliy_store.$entityLinkColumn = $linkColumn AND " .
                "default_visibiliy_store.attribute_id = $visibilityId AND default_visibiliy_store.store_id = 0",
                []
            )
            ->where('link.product_id IN (?)', $childrenIds)
            ->where("visibiliy_store.value NOT IN ($visibilityValues) OR " .
                "(visibiliy_store.value IS NULL AND default_visibiliy_store.value NOT IN ($visibilityValues))")
            ->group("e.$entityLinkColumn");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        $this->getEntity();

        if ($this->_pageSize) {
            $this->getSelect()->limitPage($this->getCurPage(), $this->_pageSize);
        }

        $this->printLogQuery($printQuery, $logQuery);

        $query = null;
        try {
            $query = $this->getSelect();
            $rows = $this->_fetchAll($query);
        } catch (\Exception $e) {
            $this->printLogQuery(true, true, $query);
            throw $e;
        }

        $entityIdField = $this->getEntity()->getEntityIdField();
        foreach ($rows as $row) {
            $entityId = $row[$entityIdField];
            if (isset($this->_itemsById[$entityId])) {
                $this->_itemsById[$entityId][] = $row;
            } else {
                $this->_itemsById[$entityId] = [$row];
            }
            foreach ($this->multiSelectAttributes as $attrCode) {
                if (!isset($row[$attrCode])) {
                    continue;
                }

                $row[$attrCode] = (array) $row[$attrCode];

                if (isset($this->_items[$entityId][$attrCode])) {
                    $row[$attrCode] = array_values(
                        array_unique(
                            array_merge($row[$attrCode], $this->_items[$entityId][$attrCode])
                        )
                    );
                }
            }
            $this->_items[$entityId] = $row;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $entityIdField = $this->getEntity()->getEntityIdField();
        $entityId      = $valueInfo[$entityIdField];
        if (!isset($this->_itemsById[$entityId])) {
            throw new LocalizedException(__('Data integrity: No header row found for attribute'));
        }

        $attributeCode = array_search($valueInfo['attribute_id'], $this->_selectAttributes);
        if (!$attributeCode) {
            $attribute = $this->_eavConfig->getAttribute(
                $this->getEntity()->getType(),
                $valueInfo['attribute_id']
            );
            $attributeCode = $attribute->getAttributeCode();
        }

        foreach ($this->_itemsById[$entityId] as &$data) {
            $data[$attributeCode] = $valueInfo['value'];
            $this->_items[$entityId][$attributeCode] = $valueInfo['value'];
        }
        unset($data);

        return $this;
    }
}
