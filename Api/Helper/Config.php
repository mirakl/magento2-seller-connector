<?php
namespace MiraklSeller\Api\Helper;

use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Log\LogOptions;

class Config extends AbstractHelper
{
    const XML_PATH_API_DEVELOPER_LOG_OPTION = 'mirakl_seller_api_developer/log/log_option';
    const XML_PATH_API_DEVELOPER_LOG_FILTER = 'mirakl_seller_api_developer/log/log_filter';

    /**
     * @var MagentoConfig
     */
    protected $configuration;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var bool
     */
    protected $_apiEnabled = true;

    /**
     * @param   Context                 $context
     * @param   MagentoConfig           $configuration
     * @param   StoreManagerInterface   $storeManager
     * @param   Serializer              $serializer
     */
    public function __construct(
        Context $context,
        MagentoConfig $configuration,
        StoreManagerInterface $storeManager,
        Serializer $serializer
    ) {
        parent::__construct($context);
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * @return  $this
     */
    public function disable()
    {
        Manager::disable();

        return $this->setApiEnabled(false);
    }

    /**
     * @return  $this
     */
    public function enable()
    {
        Manager::enable();

        return $this->setApiEnabled(true);
    }

    /**
     * Returns a config value
     *
     * @param   string  $path
     * @param   mixed   $store
     * @return  mixed
     */
    public function getValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return  bool
     */
    public function isEnabled()
    {
        return $this->_apiEnabled;
    }

    /**
     * Enable or disable API
     *
     * @param   bool    $flag
     * @return  $this
     */
    public function setApiEnabled($flag)
    {
        $this->_apiEnabled = (bool) $flag;

        return $this;
    }

    /**
     * @param   mixed   $store
     * @return  int
     */
    public function getApiLogOption($store = null)
    {
        return (int) $this->getValue(self::XML_PATH_API_DEVELOPER_LOG_OPTION, $store);
    }

    /**
     * @param   mixed   $store
     * @return  string
     */
    public function getApiLogFilter($store = null)
    {
        return $this->getValue(self::XML_PATH_API_DEVELOPER_LOG_FILTER, $store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function isApiLogEnabled($store = null)
    {
        return $this->getApiLogOption($store) !== LogOptions::LOG_DISABLED;
    }

    /**
     * @return  void
     */
    protected function resetConfig()
    {
        $this->storeManager->getStore()->resetConfig();
    }

    /**
     * Set a config value
     *
     * @param   string  $path
     * @param   string  $value
     * @param   string  $scope
     * @param   int     $scopeId
     * @return  void
     */
    public function setValue($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configuration->saveConfig($path, $value, $scope, $scopeId);
        $this->resetConfig();
    }
}
