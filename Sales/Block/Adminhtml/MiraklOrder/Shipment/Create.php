<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\Shipment;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Common\Domain\Collection\Shipping\ShippingTypeWithDescriptionCollection;
use Mirakl\MMP\Common\Domain\Shipping\ShippingTypeWithDescription;
use MiraklSeller\Api\Helper\Shipping as ShippingApi;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;

class Create extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var ShippingApi
     */
    protected $shippingApi;

    /**
     * @var string
     */
    protected $_template = 'MiraklSeller_Sales::mirakl_order/shipment/create.phtml';

    /**
     * @param   Template\Context            $context
     * @param   Registry                    $coreRegistry
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   ShippingApi                 $shippingApi
     * @param   array                       $data
     */
    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        ShippingApi $shippingApi,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->coreRegistry = $coreRegistry;
        $this->connectionFactory = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->shippingApi = $shippingApi;
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return $this->getConnectionById($this->getOrder()->getMiraklConnectionId());
    }

    /**
     * Retrieves Mirakl connection by id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function getConnectionById($connectionId)
    {
        /** @var Connection $connection */
        $connection = $this->connectionFactory->create();

        /** @var \MiraklSeller\Api\Model\ResourceModel\Connection $connectionResource */
        $connectionResource = $this->connectionResourceFactory->create();
        $connectionResource->load($connection, $connectionId);

        return $connection;
    }

    /**
     * @return  Order
     */
    public function getOrder()
    {
        return $this->getShipment()->getOrder();
    }

    /**
     * @return  Order\Shipment
     */
    public function getShipment()
    {
        return $this->coreRegistry->registry('current_shipment');
    }

    /**
     * @return  string
     */
    public function getShippingDescription()
    {
        return (string) $this->getOrder()->getShippingDescription();
    }

    /**
     * @return  ShippingTypeWithDescriptionCollection
     */
    public function getShippingTypes()
    {
        return $this->shippingApi->getShippingTypes($this->getConnection());
    }

    /**
     * @return  bool
     */
    public function isShipmentMandatoryTracking()
    {
        $shippingLabel = $this->getShippingDescription();
        $shippingTypes = $this->getShippingTypes();

        /** @var ShippingTypeWithDescription $shippingType */
        foreach ($shippingTypes as $shippingType) {
            if ($shippingType->getLabel() !== $shippingLabel) {
                continue;
            }

            return (bool) $shippingType->getData('mandatory_tracking');
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if (!$this->getOrder()->getMiraklOrderId() || !$this->isShipmentMandatoryTracking()) {
            return ''; // Do not load template contents if not a Mirakl order
        }

        return parent::_toHtml();
    }
}
