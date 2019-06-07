<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use MiraklSeller\Api\Model\Connection as ConnectionModel;
use MiraklSeller\Api\Model\ConnectionFactory as ConnectionModelFactory;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionResource;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;

class InlineEdit extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Api::connections';

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var ConnectionModelFactory
     */
    protected $connectionModelFactory;

    /**
     * @param Context                   $context
     * @param ConnectionResourceFactory $connectionResourceFactory
     * @param ConnectionModelFactory    $connectionModelFactory
     */
    public function __construct(
        Context $context,
        ConnectionResourceFactory $connectionResourceFactory,
        ConnectionModelFactory $connectionModelFactory
    ) {
        parent::__construct($context);
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->connectionModelFactory = $connectionModelFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        /** @var ConnectionResource $resourceModel */
        $resourceModel = $this->connectionResourceFactory->create();

        foreach (array_keys($postItems) as $connectionId) {
            /** @var ConnectionModel $connection */
            $connection = $this->connectionModelFactory->create();
            $resourceModel->load($connection, $connectionId);

            if (!$connection->getId()) {
                $messages[] = $this->getErrorWithConnectionId(
                    $connectionId,
                    __('This connection no longer exists.')
                );
                $error = true;

                continue;
            }

            try {
                $connectionData = $postItems[$connectionId];
                $connection->addData($connectionData);
                $connection->validateBeforeSave();
                $resourceModel->save($connection);
            } catch (LocalizedException $e) {
                $messages[] = $this->getErrorWithConnectionId($connectionId, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithConnectionId($connectionId, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithConnectionId(
                    $connectionId,
                    __('Something went wrong while saving the connection.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * @param   int     $id
     * @param   string  $errorText
     * @return  string
     */
    protected function getErrorWithConnectionId($id, $errorText)
    {
        return '[ID: ' . $id. '] ' . $errorText;
    }
}
