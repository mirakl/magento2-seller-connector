<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use MiraklSeller\Api\Model\ResourceModel\Connection;

class Delete extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $connection = $this->getConnection(true);
        } catch (NotFoundException $e) {
            return $resultRedirect->setPath('*/*/');
        }

        $title = $connection->getName();

        try {
            $resource = $this->_objectManager->get(Connection::class);
            $resource->delete($connection);
        } catch (\Exception $e) {
            $this->_eventManager->dispatch(
                'adminhtml_mirakl_seller_connection_on_delete',
                ['title' => $title, 'status' => 'fail', 'exception' => $e]
            );
            // Display error message
            $this->messageManager->addErrorMessage($e->getMessage());

            // Go back to edit form
            return $resultRedirect->setPath('*/*/edit', ['id' => $connection->getId()]);
        }

        // Display success message
        $this->messageManager->addSuccessMessage(__('The connection has been deleted.'));

        // Go to grid
        $this->_eventManager->dispatch(
            'adminhtml_mirakl_seller_connection_on_delete',
            ['title' => $title, 'status' => 'success']
        );

        return $resultRedirect->setPath('*/*/');
    }
}
