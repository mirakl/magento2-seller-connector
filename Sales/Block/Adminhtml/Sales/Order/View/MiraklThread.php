<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Shop\Domain\Collection\Reason\ReasonCollection;
use MiraklSeller\Api\Helper\Message as MessageApi;
use MiraklSeller\Api\Helper\Reason as ReasonApi;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Core\Helper\Thread as ThreadHelper;

/**
 * @method bool  getShowForm()
 * @method $this setShowForm(bool $showForm)
 */
class MiraklThread extends Template
{
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
     * @var ThreadDetails
     */
    protected $thread;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var ReasonApi
     */
    protected $reasonApi;

    /**
     * @var ThreadHelper
     */
    protected $threadHelper;

    /**
     * @param   Template\Context            $context
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   MessageApi                  $messageApi
     * @param   ReasonApi                   $reasonApi
     * @param   ThreadHelper                $threadHelper
     * @param   array                       $data
     */
    public function __construct(
        Template\Context $context,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        MessageApi $messageApi,
        ReasonApi $reasonApi,
        ThreadHelper $threadHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->messageApi                = $messageApi;
        $this->reasonApi                 = $reasonApi;
        $this->threadHelper              = $threadHelper;
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $connectionId = $this->getRequest()->getParam('connection_id');
            $this->connection = $this->connectionFactory->create();
            $this->connectionResourceFactory->create()->load($this->connection, $connectionId);
        }

        return $this->connection;
    }

    /**
     * @return  ThreadDetails
     */
    public function getThread()
    {
        if (!$this->thread && ($threadId = $this->getRequest()->getParam('thread_id'))) {
            try {
                $threadId = $this->getRequest()->getParam('thread_id');
                $this->thread = $this->messageApi->getThreadDetails($this->getConnection(), $threadId);
            } catch (\Exception $e) {
                $this->getLayout()->getMessagesBlock()->addError($e->getMessage());
            }
        }

        return $this->thread;
    }

    /**
     * @param   ThreadAttachment    $attachment
     * @return  string
     */
    public function getAttachmentUrl(ThreadAttachment $attachment)
    {
        $thread = $this->getThread();

        return $this->getUrl('mirakl_seller/thread/attachment', [
            'order_id'      => $this->getRequest()->getParam('order_id'),
            'connection_id' => $this->getConnection()->getId(),
            'thread_id'     => $thread->getId(),
            'attachment_id' => $attachment->getId(),
        ]);
    }

    /**
     * @return  string
     */
    public function getFormAction()
    {
        if ($this->getThread()) {
            return $this->getUrl('mirakl_seller/thread/postReply');
        }

        return $this->getUrl('mirakl_seller/thread/postNew');
    }

    /**
     * @return  string
     */
    public function getFormTitle()
    {
        return $this->getThread() ? __('Answer') : __('Start a Conversation');
    }

    /**
     * @return  string
     */
    public function getGridUrl()
    {
        return $this->getUrl('mirakl_seller/thread/grid', [
            'order_id' => $this->getRequest()->getParam('order_id')
        ]);
    }

    /**
     * Returns current locale
     *
     * @return  string
     */
    public function getLocale()
    {
        return $this->_scopeConfig->getValue('general/locale/code');
    }

    /**
     * @param   ThreadMessage   $message
     * @return  array
     */
    public function getRecipientNames(ThreadMessage $message)
    {
        $names = [];

        $message = $message->toArray();

        if (!empty($message['to'])) {
            foreach ($message['to'] as $recipient) {
                if (!empty($recipient['display_name'])) {
                    $names[] = $recipient['display_name'];
                }
            }
        }

        return $names;
    }

    /**
     * @return  bool
     */
    public function getRefreshList()
    {
        return (bool) $this->getRequest()->getParam('refresh_list', false);
    }

    /**
     * @param   ThreadMessage   $message
     * @return  string
     */
    public function getSenderName(ThreadMessage $message)
    {
        $message = $message->toArray();

        if (isset($message['from']['organization_details']['display_name'])) {
            return $message['from']['organization_details']['display_name'];
        }

        return $message['from']['display_name'];
    }

    /**
     * @return  ReasonCollection
     */
    public function getThreadReasons()
    {
        $reasons = new ReasonCollection();

        if (!$this->getShowForm()) {
            return $reasons;
        }

        try {
            $reasons = $this->reasonApi->getTypeReasons(
                $this->getConnection(), ReasonType::ORDER_MESSAGING, $this->getLocale()
            );
        } catch (\Exception $e) {
            $this->getLayout()->getMessagesBlock()->addError($e->getMessage());
        }

        return $reasons;
    }

    /**
     * @return  array
     */
    public function getThreadRecipients()
    {
        return [
            'CUSTOMER' => __('Customer'),
            'OPERATOR' => __('Operator'),
            'BOTH'     => __('Customer and Operator'),
        ];
    }

    /**
     * @param   ThreadMessage   $message
     * @return  bool
     */
    public function isSellerMessage(ThreadMessage $message)
    {
        return $message->getFrom()->getType() == 'SHOP_USER';
    }
}
