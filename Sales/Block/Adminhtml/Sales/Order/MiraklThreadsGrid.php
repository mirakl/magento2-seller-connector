<?php
namespace MiraklSeller\Sales\Block\Adminhtml\Sales\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\EntityFactoryInterface as CollectionEntityFactoryInterface;
use Magento\Framework\Registry;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Api\Helper\Message as MessageApi;
use MiraklSeller\Core\Helper\Thread as ThreadHelper;

class MiraklThreadsGrid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CollectionEntityFactoryInterface
     */
    protected $_collectionEntityFactory;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var ThreadHelper
     */
    protected $threadHelper;

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
     * @param   Context                             $context
     * @param   BackendHelper                       $backendHelper
     * @param   Registry                            $coreRegistry
     * @param   CollectionEntityFactoryInterface    $collectionEntityFactory
     * @param   MessageApi                          $messageApi
     * @param   ThreadHelper                        $threadHelper
     * @param   ConnectionFactory                   $connectionFactory
     * @param   ConnectionResourceFactory           $connectionResourceFactory
     * @param   array                               $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        Registry $coreRegistry,
        CollectionEntityFactoryInterface $collectionEntityFactory,
        MessageApi $messageApi,
        ThreadHelper $threadHelper,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->_coreRegistry             = $coreRegistry;
        $this->_collectionEntityFactory  = $collectionEntityFactory;
        $this->messageApi                = $messageApi;
        $this->threadHelper              = $threadHelper;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('mirakl_seller_order_messages');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
    }

    /**
     * @param   string  $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_afterToHtml($html);
        }

        return $html . '<div class="mirakl-thread-view-content"></div>';
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        if (null === $this->connection) {
            $connectionId = $this->getOrder()->getMiraklConnectionId();
            $this->connection = $this->connectionFactory->create();
            $this->connectionResourceFactory->create()->load($this->connection, $connectionId);
        }

        return $this->connection;
    }

    /**
     * @return  \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getMainButtonsHtml()
    {
        return $this->getNewThreadButtonHtml();
    }

    /**
     * @return  string
     */
    public function getNewThreadButtonHtml()
    {
        return $this->getChildHtml('new_thread_button');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        /** @var Button $buttonBlock */
        $buttonBlock = $this->getLayout()->createBlock(Button::class);

        $params = [
            'order_id'      => $this->getRequest()->getParam('order_id'),
            'connection_id' => $this->getConnection()->getId(),
        ];

        $buttonBlock->setData([
            'label' => __('Start a Conversation'),
            'class' => 'order-thread-new',
        ]);

        $buttonBlock->setDataAttribute([
            'url' => $this->getUrl('mirakl_seller/thread/view', $params)
        ]);

        $this->setChild('new_thread_button', $buttonBlock);

        return parent::_prepareLayout();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = new \Magento\Framework\Data\Collection($this->_collectionEntityFactory);

        if ($this->getRequest()->isAjax()) {
            try {
                $order = $this->getOrder();
                $connection = $this->getConnection();

                $threads = $this->messageApi->getThreads($connection, 'MMP_ORDER', $order->getMiraklOrderId());

                if ($threads->getCollection()->count()) {
                    /** @var Thread $thread */
                    foreach ($threads->getCollection() as $thread) {
                        $data = $thread->getData();
                        $data['topic'] = $this->threadHelper->getThreadTopic($connection, $thread);
                        $data['participant_names'] = $this->threadHelper->getThreadCurrentParticipantsNames($thread);
                        $collection->addItem(new DataObject($data));
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
                $this->getLayout()
                    ->getMessagesBlock()
                    ->addError($e->getMessage());
            }
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('participants', [
            'header'   => __('Participants'),
            'index'    => 'participant_names',
            'filter'   => false,
            'sortable' => false,
            'getter'   => function ($row) {
                return implode(', ', $row->getParticipantNames());
            },
        ]);

        $this->addColumn('topic', [
            'header'   => __('Topic'),
            'index'    => 'topic',
            'filter'   => false,
            'sortable' => false,
        ]);

        $this->addColumn('date_updated', [
            'type'     => 'datetime',
            'header'   => __('Updated At'),
            'index'    => 'date_updated',
            'filter'   => false,
            'sortable' => false,
        ]);

        $this->addColumn('action',
            [
                'header'   => __('Action'),
                'align'    => 'center',
                'type'     => 'action',
                'filter'   => false,
                'sortable' => false,
                'getter'   => 'getId',
                'actions'  => [
                    [
                        'caption' => __('View Conversation'),
                        'field'   => 'thread_id',
                        'class'   => 'order-thread-view',
                        'url'     => [
                            'base' => sprintf(
                                'mirakl_seller/thread/view/order_id/%d/connection_id/%d',
                                $this->getOrder()->getId(),
                                $this->getConnection()->getId()
                            )
                        ],
                    ],
                ],
            ]
        );

        return parent::_prepareColumns();
    }
}
