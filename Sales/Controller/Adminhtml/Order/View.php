<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

use Magento\Framework\Controller\ResultFactory;

class View extends AbstractOrder
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $miraklOrder = $this->getMiraklOrder($this->getConnection());
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);
        $resultPage->getConfig()->getTitle()->prepend(__('Mirakl Order #%1', $miraklOrder->getId()));

        return $resultPage;
    }
}
