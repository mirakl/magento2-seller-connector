<?php
namespace MiraklSeller\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Listing extends AbstractDb
{
    /**
     * @var array
     */
    protected $_serializableFields = [
        'builder_params' => [null, []],
        'variants_attributes' => [null, []],
        'offer_additional_fields_values' => [null, []],
    ];

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_listing', 'id');
    }
}
