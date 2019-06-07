<?php
namespace MiraklSeller\Sales\Model\Payment\Method;

use Magento\Quote\Api\Data\CartInterface;

class Mirakl extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = 'mirakl';

    /**
     * {@inheritdoc}
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return $quote && $quote->getFromMiraklOrder();
    }
}