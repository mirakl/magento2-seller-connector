<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;
use MiraklSeller\Api\Model\ResourceModel\Connection\CollectionFactory;

class NewListing extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param   Context             $context
     * @param   CollectionFactory   $collectionFactory
     * @param   array               $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return  array
     */
    public function getConnectionOption()
    {
        return $this->collectionFactory->create()->toOptionArray();
    }
}
