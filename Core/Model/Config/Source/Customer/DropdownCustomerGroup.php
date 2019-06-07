<?php
namespace MiraklSeller\Core\Model\Config\Source\Customer;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class DropdownCustomerGroup implements OptionSourceInterface
{
    /**
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param GroupCollectionFactory $groupCollectionFactory
     */
    public function __construct(GroupCollectionFactory $groupCollectionFactory)
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Retrieves all customer groups
     *
     * @return  array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = $this->groupCollectionFactory->create()->toOptionArray();

        return $this->options;
    }
}
