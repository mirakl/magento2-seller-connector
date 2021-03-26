<?php
namespace MiraklSeller\Core\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Core\Model\Listing\Builder\BuilderFactory;
use MiraklSeller\Core\Model\Listing\Builder\BuilderInterface;
use MiraklSeller\Core\Model\Listing\Builder\Standard;
use MiraklSeller\Core\Model\ResourceModel\OfferFactory as OfferResourceFactory;

/**
 * @method  $this   setBuilderModel(string $builderModel)
 * @method  $this   setBuilderParams(string|array $builderParams)
 * @method  int     getConnectionId()
 * @method  $this   setConnectionId(int $connectionId)
 * @method  bool    getIsActive()
 * @method  $this   setIsActive(bool $flag)
 * @method  string  getLastExportDate()
 * @method  $this   setLastExportDate(string $lastExportDate)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  $this   setOfferAdditionalFieldsValues(string $offerAdditionalFieldsValues)
 * @method  int     getOfferState()
 * @method  $this   setOfferState(int $offerState)
 * @method  string  getProductIdType()
 * @method  $this   setProductIdType(string $productIdType)
 * @method  string  getProductIdValueAttribute()
 * @method  $this   setProductIdValueAttribute(string $productIdValueAttribute)
 * @method  $this   setVariantsAttributes(string $variantsAttributes)
 */
class Listing extends AbstractModel
{
    /**
     * Constants used to filter listing products/offers to export
     */
    const TYPE_ALL     = 'ALL';
    const TYPE_PRODUCT = 'PRODUCT';
    const TYPE_OFFER   = 'OFFER';

    /**
     * Additional constants used to filter listing products to export
     */
    const PRODUCT_MODE_PENDING = 'PENDING';
    const PRODUCT_MODE_ERROR   = 'ERROR';
    const PRODUCT_MODE_ALL     = 'ALL';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var BuilderFactory
     */
    protected $builderFactory;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array|null
     */
    protected $productIds;

    /**
     * @var BuilderInterface
     */
    protected $builder;

    /**
     * @var string
     */
    protected $decodeMethod = 'unserialize';

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   StoreManagerInterface       $storeManager
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   BuilderFactory              $builderFactory
     * @param   OfferResourceFactory        $offerResourceFactory
     * @param   AbstractResource|null       $resource
     * @param   AbstractDb|null             $resourceCollection
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        BuilderFactory $builderFactory,
        OfferResourceFactory $offerResourceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager;
        $this->connectionFactory = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->builderFactory = $builderFactory;
        $this->offerResourceFactory = $offerResourceFactory;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Listing::class);
    }

    /**
     * Returns array of product ids for current listing
     *
     * @return  int[]
     */
    public function build()
    {
        return $this->getBuilder()->build($this);
    }

    /**
     * @param   string  $str
     * @return  mixed
     */
    protected function decode($str)
    {
        return call_user_func($this->decodeMethod, $str);
    }

    /**
     * @return  bool
     */
    public function isActive()
    {
        return (bool) $this->getIsActive();
    }

    /**
     * @return  array
     */
    public static function getAllowedProductModes()
    {
        return [
            self::PRODUCT_MODE_PENDING,
            self::PRODUCT_MODE_ERROR,
            self::PRODUCT_MODE_ALL,
        ];
    }

    /**
     * @return  array
     */
    public static function getAllowedTypes()
    {
        return [
            self::TYPE_ALL,
            self::TYPE_PRODUCT,
            self::TYPE_OFFER,
        ];
    }

    /**
     * @return  BuilderInterface
     */
    public function getBuilder()
    {
        if ($this->builder === null) {
            $this->builder = $this->builderFactory->create($this->getBuilderModel());
        }

        return $this->builder;
    }

    /**
     * @return  string
     */
    public function getBuilderModel()
    {
        $model = $this->_getData('builder_model');
        if (empty($model)) {
            $model = Standard::class;
        }

        return $model;
    }

    /**
     * @return  array
     */
    public function getBuilderParams()
    {
        $params = $this->_getData('builder_params');
        if (is_string($params)) {
            $params = $this->decode($params);
        }

        return is_array($params) ? $params : [];
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        if (null === $this->connection && $this->getConnectionId()) {
            $this->connection = $this->connectionFactory->create();
            $this->connectionResourceFactory->create()->load($this->connection, $this->getConnectionId());
        }

        return $this->connection;
    }

    /**
     * Proxy to connection's method
     *
     * @return  array
     */
    public function getOfferAdditionalFields()
    {
        if ($connection = $this->getConnection()) {
            return $connection->getOfferAdditionalFields();
        }

        return [];
    }

    /**
     * @return  array
     */
    public function getVariantsAttributes()
    {
        $values = $this->_getData('variants_attributes');
        if (is_string($values)) {
            $values = $this->decode($values);
        }

        return is_array($values) ? $values : [];
    }

    /**
     * @return  array
     */
    public function getOfferAdditionalFieldsValues()
    {
        $values = $this->_getData('offer_additional_fields_values');
        if (is_string($values)) {
            $values = $this->decode($values);
        }

        return is_array($values) ? $values : [];
    }

    /**
     * @return  array
     */
    public function getProductIds()
    {
        if (null === $this->productIds) {
            $this->productIds = $this->offerResourceFactory->create()
                ->getListingProductIds($this->getId());
        }

        return $this->productIds;
    }

    /**
     * @return  int
     */
    public function getStoreId()
    {
        return $this->getConnection()
            ? $this->getConnection()->getStoreId()
            : Store::DEFAULT_STORE_ID;
    }

    /**
     * Returns website associated with the current listing
     *
     * @return  int
     */
    public function getWebsiteId()
    {
        if ($this->getConnection()) {
            return $this->getConnection()->getWebsiteId();
        }

        // Get website of the default store view
        $defaultStore = $this->storeManager->getDefaultStoreView();

        return $defaultStore->getWebsiteId();
    }

    /**
     * @param   BuilderInterface    $builder
     */
    public function setBuilder(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param   array   $productIds
     * @return  $this
     */
    public function setProductIds(array $productIds)
    {
        $this->productIds = $productIds;

        return $this;
    }

    /**
     * @throws  \Exception
     */
    public function validate()
    {
        if (!$this->getId()) {
            throw new LocalizedException(__('This listing no longer exists.'));
        }

        if (!$this->isActive()) {
            throw new LocalizedException(__('This listing is inactive.'));
        }
    }
}
