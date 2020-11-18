<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\Model\View\Result\Redirect;

class Stop extends AbstractProcess
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        if (!$process->canStop()) {
            return $this->redirectError(__('This process cannot be stopped.'));
        }

        try {
            $process->setStatus(\MiraklSeller\Process\Model\Process::STATUS_STOPPED);
            $this->getProcessResource()->save($process);
            $this->messageManager->addSuccessMessage(__('Process has been stopped successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while stopping the process: %1.', $e->getMessage())
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/view', ['id' => $process->getId()]);
    }
}
