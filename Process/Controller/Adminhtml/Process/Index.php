<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractProcess
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);

        $resultPage->getConfig()->getTitle()->prepend(__('Process Report List'));

        return $resultPage;
    }
}
