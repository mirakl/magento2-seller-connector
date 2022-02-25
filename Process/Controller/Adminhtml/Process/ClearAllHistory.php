<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MiraklSeller\Process\Model\HistoryClearer;
use Psr\Log\LoggerInterface;

class ClearAllHistory extends Action
{
    /**
     * @var HistoryClearer
     */
    private $historyClearer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context          $context
     * @param HistoryClearer   $historyClearer
     * @param LoggerInterface  $logger
     */
    public function __construct(
        Context $context,
        HistoryClearer $historyClearer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->historyClearer = $historyClearer;
        $this->logger = $logger;
    }

    /**
     * Clear all Mirakl process history
     */
    public function execute()
    {
        try {
            $this->historyClearer->execute();
            $this->messageManager->addSuccessMessage(__('Mirakl process history has been cleared successfully.'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing Mirakl process history: %1', $e->getMessage())
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}