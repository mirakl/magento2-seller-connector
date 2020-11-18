<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ResourceModel\Process as ProcessResource;

abstract class AbstractProcess extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Process::process';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param   Context     $context
     * @param   PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return  Process
     */
    protected function getProcess()
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var Process $process */
        $process = $this->getProcessModel();
        $this->getProcessResource()->load($process, $id);

        return $process;
    }

    /**
     * @return  Process
     */
    protected function getProcessModel()
    {
        return $this->_objectManager->create(Process::class);
    }

    /**
     * @return  ProcessResource
     */
    protected function getProcessResource()
    {
        return $this->_objectManager->get(ProcessResource::class);
    }

    /**
     * @param   Page $resultPage
     * @return  Page
     */
    protected function initPage(Page $resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Mirakl Seller'), __('Mirakl Seller'));

        return $resultPage;
    }

    /**
     * @param   string  $errorMessage
     * @param   bool    $referer
     * @return  \Magento\Framework\Controller\ResultInterface
     */
    protected function redirectError($errorMessage, $referer = false)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($referer) {
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
