<?php
namespace MiraklSeller\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder as MiraklOrder;

class Data extends AbstractHelper
{
    /**
     * @var array
     */
    protected $_orderStatusList = [
        OrderState::STAGING               => 'Fraud Check Pending',
        OrderState::WAITING_ACCEPTANCE    => 'Pending Acceptance',
        OrderState::REFUSED               => 'Rejected',
        OrderState::WAITING_DEBIT         => 'Pending Debit',
        OrderState::WAITING_DEBIT_PAYMENT => 'Debit in Progress',
        OrderState::SHIPPING              => 'Shipping in Progress',
        OrderState::TO_COLLECT            => 'To Collect',
        OrderState::SHIPPED               => 'Shipped',
        OrderState::RECEIVED              => 'Received',
        OrderState::INCIDENT_OPEN         => 'Incident Open',
        OrderState::INCIDENT_CLOSED       => 'Incident Closed',
        OrderState::CLOSED                => 'Closed',
        OrderState::CANCELED              => 'Canceled',
        OrderState::REFUNDED              => 'Refunded',
    ];

    /**
     * @var array
     */
    protected $_paymentWorkflowList = [
        'PAY_ON_ACCEPTANCE'                => 'Pay on acceptance',
        'PAY_ON_DELIVERY'                  => 'Pay on delivery',
        'PAY_ON_DUE_DATE'                  => 'Pay on due date',
        'PAY_ON_SHIPMENT'                  => 'Pay on shipment',
        'NO_CUSTOMER_PAYMENT_CONFIRMATION' => 'No payment confirmation',
    ];

    /**
     * Returns list of available Mirakl order statuses
     *
     * @param   bool    $translated
     * @return  array
     */
    public function getOrderStatusList($translated = true)
    {
        $orderStatuses = $this->_orderStatusList;

        if ($translated) {
            array_walk($orderStatuses, function (&$value) {
                $value = (string) __($value);
            });
        }

        return $orderStatuses;
    }

    /**
     * Returns the status label of the given Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @param   bool        $translated
     * @return  string
     */
    public function getOrderStatusLabel(MiraklOrder $miraklOrder, $translated = true)
    {
        $statusList = $this->getOrderStatusList($translated);
        $status     = $miraklOrder->getStatus()->getState();

        return isset($statusList[$status]) ? $statusList[$status] : $status;
    }

    /**
     * Returns list of available Mirakl payment workflows
     *
     * @param   bool    $translated
     * @return  array
     */
    public function getPaymentWorkflowList($translated = true)
    {
        $paymentWorkflows = $this->_paymentWorkflowList;

        if ($translated) {
            array_walk($paymentWorkflows, function (&$value) {
                $value = (string) __($value);
            });
        }

        return $paymentWorkflows;
    }

    /**
     * Returns the payment workflow label of the given Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @param   bool        $translated
     * @return  string
     */
    public function getPaymentWorkflowLabel(MiraklOrder $miraklOrder, $translated = true)
    {
        $workflowList = $this->getPaymentWorkflowList($translated);
        $workflow     = $miraklOrder->getPaymentWorkflow();

        return isset($workflowList[$workflow]) ? $workflowList[$workflow] : $workflow;
    }
}