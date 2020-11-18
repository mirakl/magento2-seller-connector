<?php
namespace MiraklSeller\Core\Model\Listing\Download\Adapter;

use Magento\Framework\ObjectManagerInterface;

class AdapterFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param  array    $arguments
     * @return AdapterInterface
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create(AdapterInterface::class, $arguments);
    }
}
