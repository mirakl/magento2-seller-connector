<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;

class Test extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = __('Test connection:');
        try {
            $connection = $this->getConnection(true);
            $connection->validate();

            $message .= ' ' . __('SUCCESS');
            $this->messageManager->addSuccessMessage($message);
        } catch (NotFoundException $e) {
            // Message already added
        } catch (LocalizedException $e) {
            $message .= ' ' . $e->getMessage();
            $this->messageManager->addErrorMessage($message);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}