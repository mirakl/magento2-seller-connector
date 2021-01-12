<?php
namespace MiraklSeller\Api\Helper;

use Mirakl\Core\Domain\Collection\FileCollection;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyCreated;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;
use Mirakl\MMP\Common\Request\Message\DownloadThreadMessageAttachmentRequest;
use Mirakl\MMP\Common\Request\Message\ThreadReplyRequest;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadDetailsRequest;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadsRequest;
use MiraklSeller\Api\Model\Connection;

class Message extends Client\MMP
{
    /**
     * (M13) Download an attachment
     *
     * @param   Connection  $connection
     * @param   string      $attachmentId
     * @return  FileWrapper
     */
    public function downloadThreadMessageAttachment(Connection $connection, $attachmentId)
    {
        $request = new DownloadThreadMessageAttachmentRequest($attachmentId);

        $this->_eventManager->dispatch('mirakl_seller_api_download_thread_message_attachment_before', [
            'request' => $request
        ]);

        return $this->send($connection, $request);
    }

    /**
     * (M10) Retrieve a thread
     *
     * @param   Connection   $connection
     * @param   string       $threadId
     * @return  ThreadDetails
     */
    public function getThreadDetails(Connection $connection, $threadId)
    {
        $request = new GetThreadDetailsRequest($threadId);

        $this->_eventManager->dispatch('mirakl_seller_api_get_thread_details_before', ['request' => $request]);

        return $this->send($connection, $request);
    }

    /**
     * (M11) List all threads
     *
     * @param   Connection          $connection
     * @param   string|null         $entityType
     * @param   array|string|null   $entityId
     * @param   int|null            $limit
     * @param   string|null         $token
     * @return  SeekableCollection
     */
    public function getThreads(Connection $connection, $entityType = null, $entityId = null, $limit = null, $token = null)
    {
        $request = new GetThreadsRequest();

        if ($entityType) {
            $request->setEntityType($entityType);
        }

        if ($entityId) {
            $request->setEntityId($entityId);
        }

        if ($limit) {
            $request->setLimit($limit);
        }

        if ($token) {
            $request->setPageToken($token);
        }

        $this->_eventManager->dispatch('mirakl_seller_api_get_threads_before', ['request' => $request]);

        return $this->send($connection, $request);
    }

    /**
     * (M11) List all threads from page token
     *
     * @param   Connection  $connection
     * @param   string      $token
     * @return  SeekableCollection
     */
    public function getThreadsFromPageToken(Connection $connection, $token)
    {
        return $this->getThreads($connection, null, null, null, $token);
    }

    /**
     * (M12) Reply to a thread
     *
     * @param   Connection              $connection
     * @param   string                  $threadId
     * @param   ThreadReplyMessageInput $messageInput
     * @param   FileWrapper[]           $files
     * @return  ThreadReplyCreated
     */
    public function replyToThread(Connection $connection, $threadId, ThreadReplyMessageInput $messageInput, $files = null)
    {
        $request = new ThreadReplyRequest($threadId, $messageInput);

        if ($files && count($files)) {
            $request->setFiles(new FileCollection($files));
        }

        $this->_eventManager->dispatch('mirakl_seller_api_reply_to_thread_before', ['request' => $request]);

        return $this->send($connection, $request);
    }
}
