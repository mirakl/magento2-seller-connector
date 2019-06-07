<?php
namespace MiraklSeller\Core\Model\Listing\Export\AdditionalField;

class Formatter
{
    /**
     * {@inheritdoc}
     */
    public function format(array $field, $value)
    {
        // Transform potential array to string
        if (is_array($value)) {
            $value = implode(',', $value);
        } elseif ($field['type'] == 'DATE' && !empty($value)) {
            $value = date('c', strtotime($value)); // Date (ISO 8601 Format)
        } elseif ($field['type'] == 'BOOLEAN') {
            $value = $value ? 'true' : 'false';
        }

        return $value;
    }
}