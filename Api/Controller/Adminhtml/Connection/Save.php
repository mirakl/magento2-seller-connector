<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Validator\Exception;
use MiraklSeller\Api\Model\ResourceModel\Connection;

class Save extends AbstractConnection
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param   Context                 $context
     * @param   Registry                $coreRegistry
     * @param   DataPersistorInterface  $dataPersistor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $coreRegistry);
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data && isset($data['main'])) {
            $data = $this->flatData($data);

            $model = $this->getConnection(false, isset($data['id']) ? $data['id'] : null);
            $model->setData($data);

            $this->_eventManager->dispatch(
                'mirakl_seller_connection_prepare_save',
                ['connection' => $model, 'request' => $this->getRequest()]
            );  

            try {
                $model->validateBeforeSave();
            } catch (Exception $e) {
                foreach ($e->getErrors() as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }

                return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
            }

            try {
                /** @var Connection $resource */
                $resource = $this->_objectManager->get(Connection::class);
                $resource->save($model);

                $model->validate();

                $this->messageManager->addSuccessMessage(__('The connection has been saved.'));
                $this->dataPersistor->clear('mirakl_seller_connection');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the connection.'));
            }

            $this->dataPersistor->set('mirakl_seller_connection', $data);

            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param   array   $data
     * @return  array
     */
    private function flatData(array $data)
    {
        $result = [];
        foreach ($data as $fields) {
            if (is_array($fields)) {
                $result = array_merge($result, $fields);
            }
        }

        return $result;
    }
}