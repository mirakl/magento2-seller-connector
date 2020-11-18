<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\Model\View\Result\Redirect;

class Delete extends AbstractProcess
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

        try {
            $this->getProcessResource()->delete($process);
            $this->messageManager->addSuccessMessage(__('Process has been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting the process: %1.', $e->getMessage())
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
