<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Validator\Exception;

class Validate extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $error = false;
        $messages = [];

        $model = $this->getConnection();
        $model->setData($data);

        $this->_eventManager->dispatch(
            'mirakl_seller_connection_prepare_validate',
            ['connection' => $model, 'request' => $this->getRequest()]
        );  

        try {
            $model->validateBeforeSave();
        } catch (Exception $e) {
            foreach ($e->getErrors() as $errorMessage) {
                $messages[] = $errorMessage;
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}