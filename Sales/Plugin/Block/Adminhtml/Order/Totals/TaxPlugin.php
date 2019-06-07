<?php
namespace MiraklSeller\Sales\Plugin\Block\Adminhtml\Order\Totals;

use Magento\Sales\Block\Adminhtml\Order\Totals\Tax;

class TaxPlugin
{
    /**
     * @param   Tax     $subject
     * @param   array   $result
     * @return  array
     */
    public function afterGetFullTaxInfo(Tax $subject, $result)
    {
        foreach ($result as &$info) {
            if (isset($info['percent']) && (float) $info['percent'] === 0.0) {
                $info['percent'] = null; // hide tax % if equals 0
            }
        }

        return $result;
    }
}