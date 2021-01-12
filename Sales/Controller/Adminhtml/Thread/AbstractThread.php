<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Thread;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use MiraklSeller\Api\Helper\Message as MessageApi;
use MiraklSeller\Api\Helper\Order as OrderApi;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

abstract class AbstractThread extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Sales::orders';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var OrderApi
     */
    protected $orderApi;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @param   Context                     $context
     * @param   PageFactory                 $resultPageFactory
     * @param   OrderRepositoryInterface    $orderRepository
     * @param   MessageApi                  $messageApi
     * @param   OrderApi                    $orderApi
     * @param   ConnectionLoader            $connectionLoader
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderRepositoryInterface $orderRepository,
        MessageApi $messageApi,
        OrderApi $orderApi,
        ConnectionLoader $connectionLoader
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository   = $orderRepository;
        $this->messageApi        = $messageApi;
        $this->orderApi          = $orderApi;
        $this->connectionLoader  = $connectionLoader;
    }

    /**
     * @return  Connection
     * @throws  NotFoundException
     */
    protected function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  OrderInterface
     */
    protected function getOrder()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        return $this->orderRepository->get($orderId);
    }

    /**
     * @return  ThreadDetails|null
     */
    protected function getThread()
    {
        $threadId = $this->getRequest()->getParam('thread_id');

        return $this->messageApi->getThreadDetails($this->getConnection(), $threadId);
    }

    /**
     * @return  FileWrapper[]
     */
    protected function prepareFiles()
    {
        $files = [];
        $fileData = $this->getRequest()->getFiles('file');

        if ($fileData && !empty($fileData['tmp_name'])) {
            $file = new FileWrapper(new \SplFileObject($fileData['tmp_name']));
            $file->setContentType($fileData['type']);
            $file->setFileName($fileData['name']);
            $files[] = $file;
        }

        return $files;
    }

    /**
     * @param   string  $errorMessage
     * @param   bool    $referer
     * @return  \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
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
