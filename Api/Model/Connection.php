<?php
namespace MiraklSeller\Api\Model;

use GuzzleHttp\Exception\RequestException;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Helper\Shop as ShopApi;

/**
 * @method  string  getApiUrl()
 * @method  $this   setApiUrl(string $apiUrl)
 * @method  string  getApiKey()
 * @method  $this   setApiKey(string $apiKey)
 * @method  $this   setCarriersMapping(string $carriersMapping)
 * @method  string  getErrorsCode()
 * @method  $this   setErrorsCode(string $errorsCode)
 * @method  string  getExportedPricesAttribute()
 * @method  $this   setExportedPricesAttribute(string $exportedPricesAttribute)
 * @method  $this   setExportableAttributes(string $exportableAttributes)
 * @method  string  getLastOrdersSynchronizationDate()
 * @method  $this   setLastOrdersSynchronizationDate(string $lastOrdersSynchronizationDate)
 * @method  string  getMagentoTierPricesApplyOn()
 * @method  $this   setMagentoTierPricesApplyOn(string $magentoTierPricesApplyOn)
 * @method  string  getMessagesCode()
 * @method  $this   setMessagesCode(string $messagesCode)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  $this   setOfferAdditionalFields(string $offerAdditionalFields)
 * @method  string  getShipmentSourceAlgorithm()
 * @method  $this   setShipmentSourceAlgorithm(string $shipmentSourceAlgorithm)
 * @method  int     getShopId()
 * @method  $this   setShopId(int $shopId)
 * @method  string  getSkuCode()
 * @method  $this   setSkuCode(string $skuCode)
 * @method  int     getStoreId()
 * @method  $this   setStoreId(int $storeId)
 * @method  string  getSuccessSkuCode()
 * @method  $this   setSuccessSkuCode(string $successSkuCode)
 */
class Connection extends AbstractModel
{
    const VOLUME_PRICING   = 'VOLUME_PRICING';
    const VOLUME_DISCOUNTS = 'VOLUME_DISCOUNTS';

    /**
     * @var string
     */
    protected $_eventPrefix = 'mirakl_api_connection';

    /**
     * @var string
     */
    protected $_eventObject = 'connection';

    /**
     * @var ShopApi
     */
    protected $shopApi;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    private $productAttributes = [];

    public function __construct(
        Context $context,
        Registry $registry,
        ShopApi $shopApi,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ProductAttributeRepositoryInterface $attributeRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->shopApi = $shopApi;
        $this->storeManager = $storeManager;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Connection::class);
    }

    /**
     * Returns the connection base URL in order to build Mirakl URLs easily from it
     *
     * @return  string|false
     */
    public function getBaseUrl()
    {
        if (!$apiUrl = $this->getApiUrl()) {
            return false;
        }

        $parts = parse_url($apiUrl);
        $url = sprintf('%s://%s', $parts['scheme'], $parts['host']);

        return $url;
    }

    /**
     * @return  array
     */
    public function getCarriersMapping()
    {
        $values = $this->_getData('carriers_mapping');
        if (empty($values)) {
            $values = [];
        } elseif (is_string($values)) {
            $values = json_decode($values, true);
        }

        return $values;
    }

    /**
     * @return  array
     */
    public function getOfferAdditionalFields()
    {
        $fields = $this->_getData('offer_additional_fields');
        if (empty($fields)) {
            $fields = [];
        } elseif (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        return $fields;
    }

    /**
     * @return  array
     */
    public function getExportableAttributes()
    {
        $fields = $this->_getData('exportable_attributes');
        if (empty($fields)) {
            $fields = [];
        } elseif (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        $attributes = [];
        foreach ($fields as $attributeId) {
            if (!isset($this->productAttributes[$attributeId])) {
                try {
                    $attribute = $this->attributeRepository->get($attributeId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                $this->productAttributes[$attributeId] = $attribute->getAttributeCode();
            }
            $attributes[$attributeId] = $this->productAttributes[$attributeId];
        }

        return $attributes;
    }

    /**
     * Returns website associated with the current connection
     *
     * @return  int
     */
    public function getWebsiteId()
    {
        if ($this->getStoreId() != Store::DEFAULT_STORE_ID) {
            // Get website of connection's associated store
            $websiteId = $this->storeManager->getStore($this->getStoreId())->getWebsiteId();
        } else {
            // Get website of the default store view
            $defaultStore = $this->storeManager->getDefaultStoreView();
            $websiteId = $defaultStore->getWebsiteId();
        }

        return $websiteId;
    }

    /**
     * Validates a connection
     *
     * @return  void
     * @throws  LocalizedException
     */
    public function validate()
    {
        try {
            $this->shopApi->getAccount($this);
        } catch (RequestException $e) {
            switch ($e->getCode()) {
                case 401:
                    throw new LocalizedException(__('CONN-03: You are not authorized to use the API. Please check your API key.'));
                    break;
                case 404:
                    throw new LocalizedException(__('CONN-02: The API cannot be reached. Please check the API URL.'));
                    break;
                default:
                    throw new LocalizedException(__('CONN-01: Unexpected system error. Mirakl cannot be reached.'));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('An error occurred: %1', $e->getMessage()));
        }
    }
}
