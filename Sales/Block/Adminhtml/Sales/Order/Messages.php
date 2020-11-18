<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Sales\Order;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Common\Domain\Collection\Message\OrderMessageCollection;
use Mirakl\MMP\Common\Domain\Message\OrderMessage;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;

class Messages extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $_template = 'sales/order/messages.phtml';

    /**
     * @param   Template\Context            $context
     * @param   Registry                    $registry
     * @param   ApiOrder                    $apiOrder
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   array                       $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        ApiOrder $apiOrder,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry                  = $registry;
        $this->apiOrder                  = $apiOrder;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
    }

    /**
     * @return  OrderMessageCollection
     */
    public function getAllOrderMessages()
    {
        $messages = $this->getMiraklOrderMessages();

        // Merge Mirakl messages with Magento comments
        foreach ($this->getMagentoOrder()->getStatusHistoryCollection() as $comment) {
            /** @var \Magento\Sales\Model\Order\Status\History $comment */
            if (!$comment->getComment()) {
                continue;
            }
            $createdAt = new \DateTime($comment->getCreatedAt());
            $messages->add([
                'source'       => 'magento',
                'date_created' => $createdAt,
                'subject'      => '',
                'body'         => $comment->getComment(),
                'user_sender'  => [
                    'name' => $this->getConnection()->getName(),
                    'type' => UserType::SHOP,
                ],
            ]);
        }

        // Sort messages by creation date
        $items = $messages->getItems();
        usort($items, function (OrderMessage $a, OrderMessage $b) {
            return $a->getDateCreated() <= $b->getDateCreated() ? 1 : -1;
        });

        $messages->setItems($items);

        return $messages;
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        if (null === $this->connection) {
            $connectionId = $this->getMagentoOrder()->getMiraklConnectionId();
            $this->connection = $this->connectionFactory->create();
            $this->connectionResourceFactory->create()->load($this->connection, $connectionId);
        }

        return $this->connection;
    }

    /**
     * @return  Order
     */
    public function getMagentoOrder()
    {
        return $this->registry->registry('sales_order');
    }

    /**
     * @return  ShopOrder
     */
    public function getMiraklOrder()
    {
        return $this->registry->registry('mirakl_order');
    }

    /**
     * @return  OrderMessageCollection
     */
    public function getMiraklOrderMessages()
    {
        if ($connection = $this->getConnection()) {
            return $this->apiOrder->getOrderMessages($connection, $this->getMiraklOrder());
        }

        return new OrderMessageCollection();
    }

    /**
     * Builds the sender name of the specified order message
     *
     * @param   OrderMessage    $message
     * @return  string
     */
    public function getSenderName(OrderMessage $message)
    {
        return $message->getUserSender()->getName();
    }

    /**
     * Returns true if the given message was sent by the customer
     *
     * @param   OrderMessage    $message
     * @return  bool
     */
    public function isCustomerMessage(OrderMessage $message)
    {
        return $message->getUserSender()->getType() == UserType::CUSTOMER;
    }

    /**
     * Returns true if the given message was sent by the shop
     *
     * @param   OrderMessage    $message
     * @return  bool
     */
    public function isShopMessage(OrderMessage $message)
    {
        return $message->getUserSender()->getType() == UserType::SHOP;
    }
}