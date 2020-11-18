<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit\Tab\Product;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderFactory;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Offer;
use MiraklSeller\Core\Model\ResourceModel\Offer as OfferResource;

class ProductListingHeader extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var OfferResource
     */
    protected $offerResource;

    /**
     * @var ButtonProviderFactory
     */
    protected $buttonProviderFactory;

    /**
     * @param Template\Context      $context
     * @param Registry              $registry
     * @param OfferResource         $offerResource
     * @param ButtonProviderFactory $buttonProviderFactory
     * @param array                 $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        OfferResource $offerResource,
        ButtonProviderFactory $buttonProviderFactory,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->offerResource = $offerResource;
        $this->buttonProviderFactory = $buttonProviderFactory;

        parent::__construct($context, $data);
    }

    /**
     * @return ButtonProviderInterface[]
     */
    public function getButtons()
    {
        return [
            'download' => $this->buttonProviderFactory->create(DownloadButton::class),
            'clear_all' => $this->buttonProviderFactory->create(ClearAllButton::class),
        ];
    }

    /**
     * @return string
     */
    public function getButtonsHtml()
    {
        $html = [];
        foreach ($this->getButtons() as $button) {
            $html[] = $this->getChildBlock('widget_button')
                ->setData($button->getButtonData())
                ->toHtml();
        }

        return implode(' ', $html);
    }

    /**
     * @return  array
     */
    public function getFailedProductsLabels()
    {
        $failedProducts = $this->offerResource->getNbListingFailedProductsByStatus($this->getListing()->getId());
        $failedOffers   = $this->offerResource->getNbListingFailedOffers($this->getListing()->getId());

        if (!empty($failedProducts)) {
            foreach (Offer::getProductStatusLabels() as $status => $label) {
                if (array_key_exists($status, $failedProducts)) {
                    $failedProducts[$status]['product_import_status'] = $label;
                }
            }
        }
        if (!empty($failedOffers['count'])) {
            $failedProducts[Offer::OFFER_ERROR]['product_import_status'] = 'Import price & stock failed';
            $failedProducts[Offer::OFFER_ERROR]['count'] = $failedOffers['count'];
        }

        return $failedProducts;
    }

    /**
     * @return Listing
     */
    public function getListing()
    {
        return $this->coreRegistry->registry('mirakl_seller_listing');
    }

    /**
     * @return  int
     */
    public function getNbSuccessProducts()
    {
        return $this->offerResource->getNbListingSuccessProducts($this->getListing()->getId());
    }
}