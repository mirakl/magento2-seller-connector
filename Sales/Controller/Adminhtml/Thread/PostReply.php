<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\Exception\InputException;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;

class PostReply extends AbstractThread
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $connection = $this->getConnection();
            $thread = $this->getThread();

            $data = $this->getRequest()->getPostValue();

            if (empty($data['recipients']) || empty($data['body'])) {
                throw new InputException(__('Missing or invalid data specified.'));
            }

            $messageInput = [
                'body' => nl2br($data['body']),
                'to'   => $this->getTo($thread, $data['recipients']),
            ];

            // Send the message to Mirakl (API M12)
            $this->messageApi->replyToThread(
                $connection,
                $thread->getId(),
                new ThreadReplyMessageInput($messageInput),
                $this->prepareFiles()
            );

            $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));
        } catch (BadResponseException $e) {
            $message = $e->getMessage();
            $response = \Mirakl\parse_json_response($e->getResponse());
            if (!empty($response['message'])) {
                $message = $response['message'];
            } elseif (!empty($response['errors'][0]['message'])) {
                $message = $response['errors'][0]['message'];
            }
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $message));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }

        $this->_forward('view');
    }

    /**
     * @param   ThreadDetails   $thread
     * @param   string          $recipients
     * @return  array
     */
    protected function getTo(ThreadDetails $thread, $recipients)
    {
        $to = [];

        $addCustomer = ($recipients === 'CUSTOMER' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        /** @var ThreadParticipant $participant */
        foreach ($thread->getAuthorizedParticipants() as $participant) {
            if ($participant->getType() == 'CUSTOMER' && $addCustomer) {
                $to[] = ['type' => 'CUSTOMER', 'id' => $participant->getId()];
            } elseif ($participant->getType() == 'OPERATOR' && $addOperator) {
                $to[] = ['type' => 'OPERATOR'];
            }
        }

        return $to;
    }
}
