<?php
namespace MiraklSeller\Api\Model\ResourceModel;

use Magento\Framework\DataObject;

/**
 * Compatibility with Magento 2.2 that now uses JSON serializer for serializable fields
 */
trait ArraySerializableFieldsTrait
{
    /**
     * {@inheritdoc}
     */
    protected function _serializeField(DataObject $object, $field, $defaultValue = null, $unsetEmpty = false)
    {
        $value = $object->getData($field);
        if (empty($value) && $unsetEmpty) {
            $object->unsetData($field);
        } else {
            $object->setData($field, serialize($value ?: $defaultValue));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _unserializeField(DataObject $object, $field, $defaultValue = null)
    {
        $value = $object->getData($field);

        if ($value) {
            $unserializedValue = @unserialize($value);
            $value = $unserializedValue !== false || $value === 'b:0;' ? $unserializedValue : $value;
        }

        if (empty($value)) {
            $object->setData($field, $defaultValue);
        } else {
            $object->setData($field, $value);
        }
    }
}