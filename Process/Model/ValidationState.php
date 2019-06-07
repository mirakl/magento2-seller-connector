<?php
namespace MiraklSeller\Process\Model;

/**
 * Solve " Element 'mirakl_seller_process_view': This element is not expected."
 * When merging our definition.xml file into an XML document with schema validation.
 */
class ValidationState extends \Magento\Framework\App\Arguments\ValidationState
{
    /**
     * @return  bool
     */
    public function isValidationRequired()
    {
        return false;
    }
}