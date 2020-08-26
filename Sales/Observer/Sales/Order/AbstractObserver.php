<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Backend\App\Action;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Event;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Sales\Model\Synchronize\Order as OrderSynchronizer;

abstract class AbstractObserver
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var OrderSynchronizer
     */
    protected $synchronizeOrder;

    /**
     * @var ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @param   ManagerInterface            $messageManager
     * @param   OrderRepositoryInterface    $orderRepository
     * @param   Registry                    $registry
     * @param   ViewInterface               $view
     * @param   ApiOrder                    $apiOrder
     * @param   OrderSynchronizer           $synchronizeOrder
     * @param   ConnectionHelper            $connectionHelper
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     */
    public function __construct(
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        Registry $registry,
        ViewInterface $view,
        ApiOrder $apiOrder,
        OrderSynchronizer $synchronizeOrder,
        ConnectionHelper $connectionHelper,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory
    ) {
        $this->messageManager            = $messageManager;
        $this->orderRepository           = $orderRepository;
        $this->registry                  = $registry;
        $this->view                      = $view;
        $this->apiOrder                  = $apiOrder;
        $this->synchronizeOrder          = $synchronizeOrder;
        $this->connectionHelper          = $connectionHelper;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
    }

    /**
     * Retrieves Mirakl connection by id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function getConnectionById($connectionId)
    {
        /** @var \MiraklSeller\Api\Model\Connection $connection */
        $connection = $this->connectionFactory->create();

        /** @var \MiraklSeller\Api\Model\ResourceModel\Connection $connectionResource */
        $connectionResource = $this->connectionResourceFactory->create();
        $connectionResource->load($connection, $connectionId);

        return $connection;
    }

    /**
     * Redirects user to HTTP_REFERER with an error if possible or throw an exception
     *
     * @param   string      $msg
     * @param   Action|null $action
     * @throws  \Exception
     */
    protected function fail($msg, Action $action = null)
    {
        if ($action && ($refererUrl = $action->getRequest()->getServer('HTTP_REFERER'))) {
            $action->getActionFlag()->set('', Action::FLAG_NO_DISPATCH, true);
            $action->getResponse()->setRedirect($refererUrl);
        }

        throw new \Exception($msg);
    }

    /**
     * Returns Magento order ONLY IF linked to a Mirakl order
     *
     * @param   Event   $event
     * @return  Order|null
     */
    protected function getOrderFromEvent(Event $event)
    {
        /** @var \Magento\Backend\App\Action $action */
        $action = $event->getControllerAction();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $action->getRequest();

        $orderId = $request->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $this->isImportedMiraklOrder($order) ? $order : null;
    }

    /**
     * @param   Connection  $connection
     * @param   string      $miraklOrderId
     * @return  ShopOrder
     * @throws  \Exception
     */
    protected function getMiraklOrder(Connection $connection, $miraklOrderId)
    {
        $miraklOrder = $this->apiOrder->getOrderById($connection, $miraklOrderId);

        if (!$miraklOrder) {
            $this->fail(__(
                "Could not find Mirakl order for id '%1' with connection '%2'.", $miraklOrderId, $connection->getId()
            ));
        }

        $this->registry->register('mirakl_order', $miraklOrder, true);

        return $miraklOrder;
    }

    /**
     * @param   Order   $order
     * @return  bool
     */
    protected function isImportedMiraklOrder(Order $order)
    {
        return $order->getId() && $order->getMiraklConnectionId() && $order->getMiraklOrderId();
    }

    /**
     * Used to avoid escaping on last added message
     */
    protected function resetLastAddedMessageEscaping()
    {
        $this->messageManager->getMessages()->getLastAddedMessage()->setIdentifier(null);
    }
}