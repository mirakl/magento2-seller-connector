<?php
namespace MiraklSeller\Process\Block\Adminhtml\Process\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use MiraklSeller\Process\Model\Process;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        $this->context = $context;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @return Process|null
     */
    public function getProcess()
    {
        return $this->coreRegistry->registry('mirakl_seller_process');
    }

    /**
     * @return int|null
     */
    public function getProcessId()
    {
        $process = $this->getProcess();

        return $process ? $process->getId() : null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string  $route
     * @param   array   $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
