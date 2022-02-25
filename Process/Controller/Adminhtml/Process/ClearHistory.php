<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MiraklSeller\Core\Controller\Adminhtml\RawMessagesTrait;
use MiraklSeller\Process\Helper\Config;
use MiraklSeller\Process\Model\HistoryClearer;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\Process as ProcessResource;
use Psr\Log\LoggerInterface;

class ClearHistory extends Action
{
    use RawMessagesTrait;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResource
     */
    private $processResource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context          $context
     * @param ProcessFactory   $processFactory
     * @param ProcessResource  $processResource
     * @param Config           $config
     * @param LoggerInterface  $logger
     */
    public function __construct(
        Context $context,
        ProcessFactory $processFactory,
        ProcessResource $processResource,
        Config $config,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->processFactory = $processFactory;
        $this->processResource = $processResource;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Clear Mirakl processes history before a given date
     */
    public function execute()
    {
        try {
            $deleteFrom = $this->config->getProcessClearHistoryBeforeDate();

            /** @var Process $process */
            $process = $this->processFactory->create()
                ->setType(Process::TYPE_ADMIN)
                ->setName('Clear history of processes created before configured days count')
                ->setHelper(HistoryClearer::class)
                ->setMethod('execute')
                ->setParams([$deleteFrom]);

            $this->processResource->save($process);
            $this->messageManager->addSuccessMessage(__('Mirakl processes history will be cleared asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while clearing Mirakl processes history: %1', $e->getMessage())
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}