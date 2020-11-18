<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use MiraklSeller\Process\Model\Process;

class View extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return  Process
     */
    public function getProcess()
    {
        return $this->coreRegistry->registry('mirakl_seller_process');
    }
}
