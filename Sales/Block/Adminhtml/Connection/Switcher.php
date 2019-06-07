<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Connection;

use Magento\Backend\Block\Template\Context;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ResourceModel\Connection\Collection as ConnectionCollection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

/**
 * @method string getUseConfirm()
 */
class Switcher extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'MiraklSeller_Sales::connection/switcher.phtml';

    /**
     * @var string
     */
    protected $_defaultConnectionVarName = 'connection_id';

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @param   Context             $context
     * @param   ApiOrder            $apiOrder
     * @param   ConnectionLoader    $connectionLoader
     * @param   array               $data
     */
    public function __construct(
        Context $context,
        ApiOrder $apiOrder,
        ConnectionLoader $connectionLoader,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->apiOrder = $apiOrder;
        $this->connectionLoader = $connectionLoader;
    }

    /**
     * @return  Connection
     */
    public function getCurrentConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  ConnectionCollection
     */
    public function getConnections()
    {
        return $this->connectionLoader->getConnections();
    }

    /**
     * @return  string
     */
    public function getConnectionVarName()
    {
        if ($this->hasData('connection_var_name')) {
            return $this->getData('connection_var_name');
        }

        return (string) $this->_defaultConnectionVarName;
    }

    /**
     * @param   string  $varName
     * @return  $this
     */
    public function setConnectionVarName($varName)
    {
        return $this->setData('connection_var_name', (string) $varName);
    }

    /**
     * @return  string
     */
    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }

        return $this->getUrl('*/*/*', [
            '_current' => true,
            $this->getConnectionVarName() => null,
        ]);
    }
}