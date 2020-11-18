<?php
namespace MiraklSeller\Sales\Ui\Component\Control;

use Magento\Ui\Component\Action;

class AcceptAction extends Action
{
    /**
     * @return  string
     */
    protected function getMassAcceptUrl()
    {
        $context = $this->getContext();

        return $context->getUrl('mirakl_seller/order/massAccept', [
            'order_id'      => $context->getRequestParam('order_id'),
            'connection_id' => $context->getRequestParam('connection_id'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $config = $this->getConfiguration();
        $config['url'] = $this->getMassAcceptUrl();
        $this->setData('config', $config);
    }
}