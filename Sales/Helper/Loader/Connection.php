<?php
namespace MiraklSeller\Sales\Helper\Loader;

use Magento\Backend\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NotFoundException;
use MiraklSeller\Api\Model\Connection as ConnectionModel;
use MiraklSeller\Api\Model\ResourceModel\Connection\Collection as ConnectionCollection;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory as ConnectionCollectionFactory;

class Connection extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ConnectionCollectionFactory
     */
    protected $connectionCollectionFactory;

    /**
     * @var ConnectionCollection
     */
    protected $connections;

    /**
     * @var ConnectionModel
     */
    protected $currentConnection;

    /**
     * @param   Context                     $context
     * @param   Session                     $session
     * @param   ConnectionCollectionFactory $connectionCollectionFactory
     */
    public function __construct(
        Context $context,
        Session $session,
        ConnectionCollectionFactory $connectionCollectionFactory
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->connectionCollectionFactory = $connectionCollectionFactory;
    }

    /**
     * @return  ConnectionModel
     * @throws  NotFoundException
     */
    public function getCurrentConnection()
    {
        if (null === $this->currentConnection) {
            $this->currentConnection = $this->getConnections()->getItemById($this->getConnectionId());

            if (!$this->currentConnection) {
                $this->currentConnection = $this->getConnections()->getFirstItem();
            }

            if (!$this->currentConnection->getId()) {
                throw new NotFoundException(__('Could not find Mirakl Marketplace connection'));
            }
        }

        return $this->currentConnection;
    }

    /**
     * @return  int
     */
    public function getConnectionId()
    {
        $defaultConnectionId = $this->session->getMiraklConnectionId();

        if ($connectionId = $this->_getRequest()->getParam('connection_id', $defaultConnectionId)) {
            $this->session->setMiraklConnectionId($connectionId);

            return $connectionId;
        }

        return $this->getConnections()->getFirstItem()->getId();
    }

    /**
     * @return  ConnectionCollection
     */
    public function getConnections()
    {
        if (null === $this->connections) {
            $this->connections = $this->connectionCollectionFactory->create();
        }

        return $this->connections;
    }
}