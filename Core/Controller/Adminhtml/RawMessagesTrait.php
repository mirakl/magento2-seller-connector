<?php
namespace MiraklSeller\Core\Controller\Adminhtml;

/**
 * @property \Magento\Framework\Message\ManagerInterface $messageManager
 */
trait RawMessagesTrait
{
    /**
     * @param   string      $message
     * @param   string|null $group
     */
    protected function addRawErrorMessage($message, $group = null)
    {
        $this->messageManager->addErrorMessage($message, $group);
        $this->resetLastMessageIdentifier();
    }

    /**
     * @param   string      $message
     * @param   string|null $group
     */
    protected function addRawSuccessMessage($message, $group = null)
    {
        $this->messageManager->addSuccessMessage($message, $group);
        $this->resetLastMessageIdentifier();
    }

    /**
     * Sets message identifier to NULL to avoid escaping on display
     */
    private function resetLastMessageIdentifier()
    {
        $this->messageManager->getMessages()->getLastAddedMessage()->setIdentifier(null);
    }
}
