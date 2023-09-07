<?php

namespace MiraklSeller\Sales\Plugin\Model\Quote\Item;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class RepresentProductPlugin
{
    /**
     * @param QuoteItem $subject
     * @param \Closure  $proceed
     * @param Product   $product
     * @return bool
     */
    public function aroundRepresentProduct(QuoteItem $subject, \Closure $proceed, Product $product)
    {
        $quote = $subject->getQuote();

        // Each Mirakl order line should be created in a separate Magento order item
        if ($quote && $quote->getFromMiraklOrder()) {
            return false;
        }

        return $proceed($product);
    }
}