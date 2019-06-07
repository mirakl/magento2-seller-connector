<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Forward;

class NewAction extends AbstractConnection
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $resultForward->forward('edit');
    }
}