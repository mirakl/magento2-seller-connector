<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

use MiraklSeller\Core\Controller\Adminhtml\RawMessagesTrait;

class Import extends AbstractOrder
{
    use RawMessagesTrait;

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

            // Import the Mirakl order into Magento
            /** @var \MiraklSeller\Sales\Helper\Order\Import $orderImportHelper */
            $orderImportHelper = $this->_objectManager->get(\MiraklSeller\Sales\Helper\Order\Import::class);
            $order = $orderImportHelper->importMiraklOrder($connection, $miraklOrder);

            /** @var \Magento\Framework\Escaper $escaper */
            $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
            $this->addRawSuccessMessage(__(
                'Order has been imported successfully: <a href="%1" title="%2">%3</a>.',
                $this->getUrl('sales/order/view', ['order_id' => $order->getId()]),
                $escaper->escapeHtml(__('View imported order')),
                $order->getIncrementId()
            ));

            $params = $this->getRequest()->getParams();

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
