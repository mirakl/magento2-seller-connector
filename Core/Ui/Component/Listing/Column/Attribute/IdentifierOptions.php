<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column\Attribute;

class IdentifierOptions extends AllOptions
{
    /**
     * {@inheritdoc}
     */
    public function getAttributeCollection()
    {
        $collection = parent::getAttributeCollection()
            ->addFieldToFilter('is_global', '1');

        return $collection;
    }
}
