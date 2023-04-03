<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory as ProcessCollectionFactory;

class MassDelete extends AbstractProcess
{
    /**
     * @var Filter
     */
    private $massActionFilter;

    /**
     * @var ProcessCollectionFactory
     */
    private $processCollectionFactory;

    /**
     * @param Context                  $context
     * @param PageFactory              $resultPageFactory
     * @param ProcessCollectionFactory $processCollectionFactory
     * @param Filter                   $massActionFilter
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ProcessCollectionFactory $processCollectionFactory,
        Filter $massActionFilter
    ) {
        parent::__construct(
            $context,
            $resultPageFactory
        );
        $this->processCollectionFactory = $processCollectionFactory;
        $this->massActionFilter = $massActionFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $collection = $this->massActionFilter->getCollection($this->processCollectionFactory->create());

        try {
            foreach ($collection as $process) {
                 $this->getProcessResource()->delete($process);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting processes: %1.', $e->getMessage())
            );
        }

        $this->messageManager->addSuccessMessage(__('Processes have been deleted successfully.'));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
