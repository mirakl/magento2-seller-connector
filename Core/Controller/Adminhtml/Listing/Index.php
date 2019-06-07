<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractListing
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);

        $resultPage->getConfig()->getTitle()->prepend(__('Listing List'));

        return $resultPage;
    }
}
