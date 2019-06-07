<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractOrder
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);

        $resultPage->getConfig()->getTitle()->prepend(__('Mirakl Orders'));

        return $resultPage;
    }
}
