<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);

        $resultPage->getConfig()->getTitle()->prepend(__('Connection List'));

        return $resultPage;
    }
}
