<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\Exception\InputException;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;

class PostNew extends AbstractThread
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $params = [];
            $connection = $this->getConnection();
            $order = $this->getOrder();
            $miraklOrder = $this->orderApi->getOrderById($connection, $order->getMiraklOrderId());

            $data = $this->getRequest()->getPostValue();

            if (empty($data['recipients']) || empty($data['topic']) || empty($data['body'])) {
                throw new InputException(__('Missing or invalid data specified.'));
            }

            $messageInput = [
                'topic' => [
                    'type' => 'REASON_CODE',
                    'value' => $data['topic']
                ],
                'body'  => nl2br($data['body']),
                'to'    => $this->getTo($data['recipients']),
            ];

            // Send thread creation to Mirakl (API OR43)
            $threadCreated = $this->orderApi->createOrderThread(
                $connection,
                $miraklOrder,
                new CreateOrderThread($messageInput),
                $this->prepareFiles()
            );

            $params['thread_id'] = $threadCreated->getThreadId();
            $params['refresh_list'] = true;

            $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));
        } catch (BadResponseException $e) {
            $response = \Mirakl\parse_json_response($e->getResponse());
            $message = $response['message'] ?? $e->getMessage();
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $message));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }

        $this->_forward('view', null, null, $params);
    }

    /**
     * @param   string  $recipients
     * @return  array
     */
    protected function getTo($recipients)
    {
        $to = [];

        $addCustomer = ($recipients === 'CUSTOMER' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        if ($addCustomer) {
            $to[] = 'CUSTOMER';
        }

        if ($addOperator) {
            $to[] = 'OPERATOR';
        }

        return $to;
    }
}
