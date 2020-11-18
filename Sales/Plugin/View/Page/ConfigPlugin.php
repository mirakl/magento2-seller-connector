<?php
namespace MiraklSeller\Sales\Plugin\View\Page;

use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;

class ConfigPlugin
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $addBodyClassTriggers = [];

    /**
     * @param   Registry    $registry
     * @param   array       $addBodyClassTriggers
     */
    public function __construct(Registry $registry, $addBodyClassTriggers = [])
    {
        $this->registry = $registry;
        $this->addBodyClassTriggers = $addBodyClassTriggers;
    }

    /**
     * @param   PageConfig  $pageConfig
     * @param   \Closure    $proceed
     * @param   string      $className
     * @return  PageConfig
     */
    public function aroundAddBodyClass(PageConfig $pageConfig, \Closure $proceed, $className)
    {
        $proceed($className);

        if (in_array($className, $this->addBodyClassTriggers) && $this->isMiraklOrder()) {
            $proceed('mirakl-order');
        }

        return $pageConfig;
    }

    /**
     * @return  bool
     */
    private function isMiraklOrder()
    {
        if ($this->registry->registry('mirakl_order')) {
            return true;
        }

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        if ($invoice = $this->registry->registry('current_invoice')) {
            return (bool) $invoice->getOrder()->getMiraklOrderId();
        }

        /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
        if ($creditMemo = $this->registry->registry('current_creditmemo')) {
            return (bool) $creditMemo->getOrder()->getMiraklOrderId();
        }

        return false;
    }
}