<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

class Refuse extends AbstractOrder
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

            // Build order lines to refuse
            $orderLines = [];

            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($miraklOrder->getOrderLines() as $orderLine) {
                $orderLines[] = [
                    'id'       => $orderLine->getId(),
                    'accepted' => false,
                ];
            }

            // Refuse all items of the order
            $this->apiOrder->acceptOrder($connection, $miraklOrder->getId(), $orderLines);

            $this->_eventManager->dispatch('mirakl_seller_refuse_order_after', [
                'connection'  => $connection,
                'order'       => $miraklOrder,
                'order_lines' => $orderLines,
            ]);

            $this->messageManager->addSuccessMessage(__('Order has been refused successfully.'));
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }
}
