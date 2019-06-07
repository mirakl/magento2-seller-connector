<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $connection = $this->getConnection();

        $this->_coreRegistry->register('mirakl_seller_connection', $connection);
        
        $title = $connection->getId()
            ? __("Edit Connection '%1'", $connection->getName())
            : __('New Connection');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage)->addBreadcrumb($title, $title);
        $resultPage->getConfig()->getTitle()->prepend(__('Connections'));
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
