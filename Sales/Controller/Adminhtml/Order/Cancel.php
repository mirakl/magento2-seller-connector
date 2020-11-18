<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

class Cancel extends AbstractOrder
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            // Retrieve connection
            $connection = $this->getConnection();

            // Retrieve Mirakl order
            $miraklOrder = $this->getMiraklOrder($connection);

            $this->apiOrder->cancelOrder($connection, $miraklOrder->getId());

            $this->_eventManager->dispatch('mirakl_seller_cancel_order_after', [
                'connection' => $connection,
                'order'      => $miraklOrder,
            ]);

            $this->messageManager->addSuccessMessage(__('Order has been canceled successfully.'));
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }
}
