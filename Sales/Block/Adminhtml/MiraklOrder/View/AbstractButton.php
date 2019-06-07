<?php
namespace MiraklSeller\Sales\Block\Adminhtml\MiraklOrder\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Model\Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;
use MiraklSeller\Sales\Helper\Order as OrderHelper;

abstract class AbstractButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var MiraklOrderLoader
     */
    protected $miraklOrderLoader;

    /**
     * @param   Context             $context
     * @param   OrderHelper         $orderHelper
     * @param   ConnectionLoader    $connectionLoader
     * @param   MiraklOrderLoader   $miraklOrderLoader
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper,
        ConnectionLoader $connectionLoader,
        MiraklOrderLoader $miraklOrderLoader
    ) {
        $this->context           = $context;
        $this->orderHelper       = $orderHelper;
        $this->connectionLoader  = $connectionLoader;
        $this->miraklOrderLoader = $miraklOrderLoader;
    }

    /**
     * @return  Connection
     */
    protected function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  \Magento\Framework\Escaper
     */
    protected function getEscaper()
    {
        return $this->context->getEscaper();
    }

    /**
     * @return  Order|null
     */
    public function getMagentoOrder()
    {
        return $this->orderHelper->getOrderByMiraklOrderId($this->getMiraklOrder()->getId());
    }

    /**
     * @return  ShopOrder
     */
    protected function getMiraklOrder()
    {
        return $this->miraklOrderLoader->getCurrentMiraklOrder($this->getConnection());
    }

    /**
     * @return  \Magento\Framework\UrlInterface
     */
    protected function getUrlBuilder()
    {
        return $this->context->getUrlBuilder();
    }
}
