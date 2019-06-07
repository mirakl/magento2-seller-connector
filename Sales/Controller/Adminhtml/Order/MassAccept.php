<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

class MassAccept extends AbstractOrder
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

            $params = $this->getRequest()->getParams();
            $acceptAll = isset($params['excluded']) && $params['excluded'] === 'false';
            $acceptedOrderLineIds = array_filter($this->getRequest()->getParam('selected', []));

            // Build order lines to accept
            $orderLines = [];

            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($miraklOrder->getOrderLines() as $orderLine) {
                $orderLines[] = [
                    'id'       => $orderLine->getId(),
                    'accepted' => $acceptAll || in_array($orderLine->getId(), $acceptedOrderLineIds),
                ];
            }

            // Accept selected order lines of the order and refuse the others
            $this->apiOrder->acceptOrder($connection, $miraklOrder->getId(), $orderLines);

            $this->_eventManager->dispatch('mirakl_seller_accept_order_after', [
                'connection'  => $connection,
                'order'       => $miraklOrder,
                'order_lines' => $orderLines,
            ]);

            $this->messageManager->addSuccessMessage(__('Order has been accepted successfully.'));

            return $this->_redirect('*/*/view', [
                'order_id' => $params['order_id'],
                'connection_id' => $params['connection_id'],
                '_current' => true
            ]);
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }
}
