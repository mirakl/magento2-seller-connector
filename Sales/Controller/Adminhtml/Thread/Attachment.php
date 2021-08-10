<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;

class Attachment extends AbstractThread
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $attachmentId = $this->getRequest()->getParam('attachment_id');
            $orderId = $this->getRequest()->getParam('order_id');
            $connection = $this->getConnection();
            $thread = $this->getThread();

            if (!$this->validateAttachment($thread, $attachmentId)) {
                throw new InputException(__('Attachment not found.'));
            }

            $document = $this->messageApi->downloadThreadMessageAttachment($connection, $attachmentId);

            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $contentSize = $document->getFile()->fstat()['size'];

            $result->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', $document->getContentType(), true)
                ->setHeader('Content-Disposition', 'attachment; filename=' . $document->getFileName());

            $result->setContents($document->getFile()->fread($contentSize));

            return $result;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
        }
    }

    /**
     * @param   ThreadDetails   $thread
     * @param   string          $attachmentId
     * @return  bool
     */
    protected function validateAttachment(ThreadDetails $thread, $attachmentId)
    {
        /** @var ThreadMessage $message */
        foreach ($thread->getMessages() as $message) {
            if (!empty($message->getAttachments())) {
                /** @var ThreadAttachment $attachment */
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getId() == $attachmentId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
