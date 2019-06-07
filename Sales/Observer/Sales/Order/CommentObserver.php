<?php
namespace MiraklSeller\Sales\Observer\Sales\Order;

use Magento\Backend\App\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CommentObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * Intercept add comment on order from back office
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$order = $this->getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Action $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $action->getRequest();
        $history = $request->getParam('history', ['comment' => '']);

        $connection = $this->getConnectionById($order->getMiraklConnectionId());
        $this->getMiraklOrder($connection, $order->getMiraklOrderId()); // Just to save the Mirakl order in registry

        if (empty($history['comment']) || empty($history['is_customer_notified'])) {
            return; // Not possible to send empty comment or to send a message to the shop as a seller
        }

        $subject = __('New comment on order %1', $order->getMiraklOrderId());
        $body    = $history['comment'];

        try {
            $this->apiOrder->createOrderMessage($connection, $order->getMiraklOrderId(), $subject, $body, true);

            $this->registry->register('sales_order', $order);

            // Do not save the message in Magento because already saved in Mirakl
            $action->getActionFlag()->set('', 'no-dispatch', true);
            $this->view->loadLayout('empty');
            $this->view->renderLayout();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());
                $this->sendError($action, $result['message']);
            } catch (\InvalidArgumentException $e) {
                $this->sendError($action, $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->sendError($action, $e->getMessage());
        }
    }

    /**
     * @param   Action  $action
     * @param   string  $message
     */
    private function sendError(Action $action, $message)
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $action->getResponse();
        $response->setHeader('Content-Type', 'application/json', true);
        $response->setBody(json_encode(['error' => true, 'message' => $message]));
        $response->sendResponse();
        exit; // @codingStandardsIgnoreLine
    }
}