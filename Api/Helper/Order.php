<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Collection\Message\OrderMessageCollection;
use Mirakl\MMP\Common\Domain\Message\MessageCreated;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadCreated;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Common\Request\Order\Message\CreateOrderThreadRequest;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use Mirakl\MMP\Shop\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Order\Message\CreateOrderMessageRequest;
use Mirakl\MMP\Shop\Request\Order\Message\GetOrderMessagesRequest;
use Mirakl\MMP\Shop\Request\Order\Ship\ShipOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Tracking\UpdateOrderTrackingInfoRequest;
use Mirakl\MMP\Shop\Request\Order\Workflow\CancelOrderRequest;
use MiraklSeller\Api\Model\Connection;

class Order extends Client\MMP
{
    /**
     * (OR21) Accept or refuse order lines which are in "WAITING_ACCEPTANCE" state
     *
     * @param   Connection  $connection
     * @param   string      $orderId
     * @param   array       $orderLines
     */
    public function acceptOrder(Connection $connection, $orderId, array $orderLines)
    {
        $request = new AcceptOrderRequest($orderId, $orderLines);

        $this->send($connection, $request);
    }

    /**
     * (OR29) Cancel an order which is not yet debited to the customer
     *
     * @param   Connection  $connection
     * @param   string      $orderId
     */
    public function cancelOrder(Connection $connection, $orderId)
    {
        $request = new CancelOrderRequest($orderId);

        $this->send($connection, $request);
    }

    /**
     * (OR42) Post a message on an order
     *
     * @deprecated Use createOrderThread() instead
     *
     * @param   Connection  $connection
     * @param   string      $orderId
     * @param   string      $subject
     * @param   string      $body
     * @param   bool        $toCustomer
     * @param   bool        $toOperator
     * @return  MessageCreated
     */
    public function createOrderMessage(
        Connection $connection,
        $orderId,
        $subject,
        $body,
        $toCustomer = false,
        $toOperator = false
    ) {
        $message = [
            'subject'     => (string) $subject,
            'body'        => (string) $body,
            'to_shop'     => false, // Not possible to send a message to the seller as a seller
            'to_customer' => (bool) $toCustomer,
            'to_operator' => (bool) $toOperator,
        ];

        $request = new CreateOrderMessageRequest($orderId, $message);

        return $this->send($connection, $request);
    }

    /**
     * (OR43) Create a thread on an order
     *
     * @param   Connection          $connection
     * @param   ShopOrder           $miraklOrder
     * @param   CreateOrderThread   $thread
     * @param   FileWrapper[]       $files
     * @return  ThreadCreated
     */
    public function createOrderThread(
        Connection $connection,
        ShopOrder $miraklOrder,
        CreateOrderThread $thread,
        $files = []
    ) {
        $request = new CreateOrderThreadRequest($miraklOrder->getId(), $thread);

        if (count($files)) {
            $request->setFiles($files);
        }

        $this->_eventManager->dispatch('mirakl_seller_api_create_order_thread_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * (OR11) Fetches all Mirakl orders from specified connection
     *
     * @param   Connection  $connection
     * @param   array       $params
     * @param   string      $sortBy
     * @param   string      $dir
     * @return  ShopOrderCollection
     */
    public function getAllOrders(Connection $connection, array $params = [], $sortBy = 'dateCreated', $dir = 'DESC')
    {
        $offset = 0;
        $max    = 100;
        $orders = [];
        while (true) {
            $result = $this->getOrders($connection, $params, $offset, $max, $sortBy, $dir);
            $orders = array_merge($orders, $result->getItems());
            if (!$result->count() || count($orders) >= $result->getTotalCount()) {
                break;
            }
            $offset += $max;
        }

        return new ShopOrderCollection($orders, count($orders));
    }

    /**
     * (OR11) Get offers import information and stats
     *
     * @param   Connection  $connection
     * @param   int         $offset
     * @param   int         $max
     * @param   array       $params
     * @param   string      $sortBy
     * @param   string      $dir
     * @return  ShopOrderCollection
     */
    public function getOrders(
        Connection $connection,
        array $params = [],
        $offset = 0,
        $max = 20,
        $sortBy = 'dateCreated',
        $dir = 'DESC'
    ) {
        $request = new GetOrdersRequest();
        $request->setOffset($offset)
            ->setMax($max)
            ->setSortBy($sortBy)
            ->setDir($dir);

        if (!in_array('has_incident', $request->queryParams)) {
            // Add 'has_incident' query parameter manually if SDK version < 1.9.2
            $request->queryParams = array_merge($request->queryParams, array('has_incident'));
        }

        foreach ($params as $key => $value) {
            $request->setData($key, $value);
        }

        $this->_eventManager->dispatch('mirakl_seller_api_get_orders_before', [
            'request'    => $request,
            'connection' => $connection,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * Returns a Mirakl order with specified order id
     *
     * @param   Connection $connection
     * @param   string      $orderId
     * @return  ShopOrder|null
     */
    public function getOrderById(Connection $connection, $orderId)
    {
        $orders = $this->getOrders($connection, ['order_ids' => $orderId]);

        return $orders->first();
    }

    /**
     * (M01) Fetches messages of a Mirakl order
     *
     * @deprecated Use \MiraklSeller\Api\Helper\Message::getThreads() instead
     *
     * @param   Connection  $connection
     * @param   ShopOrder   $miraklOrder
     * @param   string      $userType
     * @param   bool        $paginate
     * @return  OrderMessageCollection
     */
    public function getOrderMessages(
        Connection $connection,
        ShopOrder $miraklOrder,
        $userType = UserType::SHOP,
        $paginate = false
    ) {
        $request = new GetOrderMessagesRequest($miraklOrder->getId());
        $request->setPaginate($paginate);
        $request->setUserType($userType);

        $this->_eventManager->dispatch('mirakl_seller_api_get_order_messages_before', [
            'request'      => $request,
            'mirakl_order' => $miraklOrder,
        ]);

        return $this->send($connection, $request);
    }

    /**
     * (OR24) Valid the shipment of a given order
     *
     * @param   Connection  $connection
     * @param   string      $orderId
     */
    public function shipOrder(Connection $connection, $orderId)
    {
        $request = new ShipOrderRequest($orderId);

        $this->send($connection, $request);
    }

    /**
     * (OR23) Update carrier tracking information of a given order
     *
     * @param   Connection  $connection
     * @param   string      $orderId
     * @param   string      $code
     * @param   string      $name
     * @param   string      $number
     * @param   string      $url
     */
    public function updateOrderTrackingInfo(
        Connection $connection, $orderId, $code = '', $name = '', $number = '', $url = ''
    ) {
        $tracking = [
            'carrier_code'    => (string) $code,
            'carrier_name'    => (string) $name,
            'carrier_url'     => (string) $url,
            'tracking_number' => (string) $number,
        ];

        $request = new UpdateOrderTrackingInfoRequest($orderId, $tracking);

        $this->send($connection, $request);
    }
}
