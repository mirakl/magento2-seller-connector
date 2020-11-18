<?php
namespace MiraklSeller\Api\Block\Adminhtml\Connection\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

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
     * @return int|null
     */
    public function getConnectionId()
    {
        $connection = $this->coreRegistry->registry('mirakl_seller_connection');

        return $connection ? $connection->getId() : null;
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
