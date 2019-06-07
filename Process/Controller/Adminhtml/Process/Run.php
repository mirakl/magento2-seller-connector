<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\Model\View\Result\Redirect;

class Run extends AbstractProcess
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

        if (!$process->canRun()) {
            return $this->redirectError(__('This process cannot be executed.'));
        }

        try {
            $process->run(true);
            $this->messageManager->addSuccessMessage(__('Process has been executed successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while executing the process: %1.', $e->getMessage())
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/view', ['id' => $process->getId()]);
    }
}
