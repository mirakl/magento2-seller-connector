<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\Model\View\Result\Redirect;

class MassDelete extends AbstractProcess
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('selected');

        if (empty($ids)) {
            return $this->redirectError(__('Please select processes to delete.'));
        }

        try {
            $this->getProcessResource()->deleteIds($ids);
            $this->messageManager->addSuccessMessage(__('Processes have been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting processes: %1.', $e->getMessage())
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
