<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Log\LogOptions;

class Config extends AbstractHelper
{
    const XML_PATH_API_DEVELOPER_LOG_OPTION = 'mirakl_seller_api_developer/log/log_option';
    const XML_PATH_API_DEVELOPER_LOG_FILTER = 'mirakl_seller_api_developer/log/log_filter';

    /**
     * @var bool
     */
    protected $_apiEnabled = true;

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
}
