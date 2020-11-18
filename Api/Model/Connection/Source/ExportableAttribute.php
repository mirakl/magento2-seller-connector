<?php
namespace MiraklSeller\Api\Model\Connection\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Core\Model\ResourceModel\Product as ProductResource;

class ExportableAttribute implements OptionSourceInterface
{
    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ProductResource $productResource
     */
    public function __construct(ProductResource $productResource)
    {
        $this->productResource = $productResource;
    }

    /**
     * Retrieves all exportable attributes
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->productResource->toOptionArray();
    }
}
