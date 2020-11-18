<?php
namespace MiraklSeller\Sales\Plugin\Model\SalesRule;

use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;

class UtilityPlugin
{
    /**
     * @param   Utility     $utility
     * @param   \Closure    $proceed
     * @param   Rule        $rule
     * @param   Address     $address
     * @return  bool
     */
    public function aroundCanProcessRule(Utility $utility, \Closure $proceed, Rule $rule, Address $address)
    {
        $quote = $address->getQuote();
        if ($quote && $quote->getFromMiraklOrder()) {
            return false; // do not apply discount rules on Mirakl orders
        }

        return $proceed($rule, $address);
    }
}