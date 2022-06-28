<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Unsync extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderResource $orderResource
     */
    public function __construct(
        Context                  $context,
        OrderRepositoryInterface $orderRepository,
        OrderResource            $orderResource
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderResource   = $orderResource;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();

            $orderId = $this->getRequest()->getParam('order_id');

            try {
                $order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This order no longer exists.'));
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            }

            $order->setMiraklSync(false);
            $this->orderResource->saveAttribute($order, 'mirakl_sync');
            $this->messageManager->addSuccessMessage(__('Order has been unsynced successfully.'));

        } catch (\Exception $e) {
            return $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
