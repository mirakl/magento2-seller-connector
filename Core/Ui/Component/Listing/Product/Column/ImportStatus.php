<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Product\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\Offer;

class ImportStatus extends Column
{
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Offer
     */
    protected $offer;

    /**
     * @var array
     */
    protected $productStatusTooltips = [
        Offer::PRODUCT_NEW                    => 'Will be sent automatically in the next export.',
        Offer::PRODUCT_PENDING                => 'Exported. Export status is about to be checked.',
        Offer::PRODUCT_TRANSFORMATION_ERROR   => 'Your data does not satisfy Mirakl validation. Check the error message. When your product is ready to be exported, click on the Export Products button.',
        Offer::PRODUCT_WAITING_INTEGRATION    => 'Marketplace integration in progress. Integration reports will be available soon in Magento.',
        Offer::PRODUCT_INTEGRATION_COMPLETE   => 'Waiting for successful Price & Stock export for final product availability in the marketplace.',
        Offer::PRODUCT_INTEGRATION_ERROR      => 'Your data does not satisfy the marketplace validation. Check the error message. When your product is ready to be exported, click on the Export Products button.',
        Offer::PRODUCT_INVALID_REPORT_FORMAT  => 'The marketplace integration report file cannot be processed.',
        Offer::PRODUCT_NOT_FOUND_IN_REPORT    => 'Product not found in the marketplace integration reports.',
        Offer::PRODUCT_SUCCESS                => 'Product has been correctly imported in the marketplace.',
    ];

    /**
     * @var array
     */
    protected $offerStatusTooltips = [
        Offer::OFFER_NEW     => 'Will be sent automatically in the next export.',
        Offer::OFFER_PENDING => 'Exported. Export status is about to be checked.',
        Offer::OFFER_SUCCESS => 'Price & Stock has been correctly imported in the marketplace.',
        Offer::OFFER_ERROR   => 'Your data does not satisfy Mirakl validation. Check the error message. When your price & stock is ready to be exported click on the Export Prices & Stocks button.',
        Offer::OFFER_DELETE  => 'Product will be deleted from the marketplace in the next export.',
    ];

    /**
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param Escaper               $escaper
     * @param Offer                 $offer
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        Offer $offer,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->escaper = $escaper;
        $this->offer = $offer;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->decorateStatus($fieldName, $item[$fieldName]);
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }

    /**
     * @param   string  $field
     * @param   string  $value
     * @return  string
     */
    public function decorateStatus($field, $value)
    {
        $className = strtolower(str_replace('_', '-', $value));
        $tooltip = $field == 'product_import_status'
            ? __($this->productStatusTooltips[$value])
            : __($this->offerStatusTooltips[$value]);
        $labels = $field == 'product_import_status'
            ? $this->offer->getProductStatusLabels()
            : $this->offer->getOfferStatusLabels();

        return sprintf(
            '<div class="admin__field-tooltip tooltip-seller"><span class="status status-%s">%s&nbsp</span><div class="admin__field-tooltip-content">%s</div></div>',
            $className,
            $labels[$value],
            $this->escaper->escapeHtml($tooltip)
        );
    }
}