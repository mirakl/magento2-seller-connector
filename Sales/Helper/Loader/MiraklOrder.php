<?php
namespace MiraklSeller\Sales\Helper\Loader;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NotFoundException;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;

class MiraklOrder extends AbstractHelper
{
    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var ShopOrder
     */
    protected $currentMiraklOrder;

    /**
     * @param   Context     $context
     * @param   ApiOrder    $apiOrder
     */
    public function __construct(
        Context $context,
        ApiOrder $apiOrder
    ) {
        parent::__construct($context);

        $this->apiOrder = $apiOrder;
    }

    /**
     * @param   Connection  $connection
     * @return  ShopOrder
     * @throws  NotFoundException
     */
    public function getCurrentMiraklOrder(Connection $connection)
    {
        if (null === $this->currentMiraklOrder) {
            if (!$miraklOrderId = $this->_getRequest()->getParam('order_id')) {
                throw new NotFoundException(__('Mirakl order id could not be found'));
            }

            $this->currentMiraklOrder = $this->apiOrder->getOrderById($connection, $miraklOrderId);

            if (!$this->currentMiraklOrder || !$this->currentMiraklOrder->getId()) {
                throw new NotFoundException(__("Could not find Mirakl order for id '%1' with connection '%2'",
                    $miraklOrderId,
                    $connection->getName()
                ));
            }
        }

        return $this->currentMiraklOrder;
    }

    /**
     * @param   Connection  $connection
     * @param   array       $params
     * @param   int         $offset
     * @param   int         $limit
     * @return  ShopOrderCollection
     */
    public function getMiraklOrders(Connection $connection, array $params = [], $offset = 0, $limit = 20)
    {
        return $this->apiOrder->getOrders($connection, $params, $offset, $limit);
    }
}