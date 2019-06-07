<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use MiraklSeller\Core\Model\Listing;

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
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     * @param   Escaper     $escaper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Escaper $escaper
    ) {
        $this->context = $context;
        $this->coreRegistry = $coreRegistry;
        $this->escaper = $escaper;
    }

    /**
     * @return Listing|null
     */
    public function getListing()
    {
        return $this->coreRegistry->registry('mirakl_seller_listing');
    }

    /**
     * @return int|null
     */
    public function getListingId()
    {
        $listing = $this->getListing();

        return $listing ? $listing->getId() : null;
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
