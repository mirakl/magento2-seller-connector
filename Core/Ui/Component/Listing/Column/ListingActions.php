<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\Listing;

class ListingActions extends Column
{
    const LISTING_URL_PATH_EDIT           = 'mirakl_seller/listing/edit';
    const LISTING_URL_PATH_REFRESH        = 'mirakl_seller/listing/refresh';
    const LISTING_URL_PATH_DOWNLOAD       = 'mirakl_seller/listingProduct/download';
    const LISTING_URL_PATH_EXPORT_PRODUCT = 'mirakl_seller/listing/productExport';
    const LISTING_URL_PATH_EXPORT_OFFER   = 'mirakl_seller/listing/offerExport';
    const LISTING_URL_PATH_DELETE         = 'mirakl_seller/listing/delete';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param   ContextInterface    $context
     * @param   UiComponentFactory  $uiComponentFactory
     * @param   UrlInterface        $urlBuilder
     * @param   Escaper             $escaper
     * @param   array               $components
     * @param   array               $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');

                $item[$name]['edit'] = [
                    'label' => __('Edit'),
                    'href' => $this->urlBuilder->getUrl(
                        self::LISTING_URL_PATH_EDIT,
                        ['id' => $item['id']]
                    ),
                ];

                $item[$name]['refresh'] = [
                    'label'   => __('Refresh Products'),
                    'title'   => __("This action will refresh the listing's products"),
                    'href'    => sprintf('%s#product_content', $this->urlBuilder->getUrl(
                        self::LISTING_URL_PATH_REFRESH,
                        ['id' => $item['id']]
                    )),
                    'confirm' => [
                        'title' => __('Refresh'),
                        'message' => __('Are you sure?'),
                    ],
                ];

                $item[$name]['download'] = [
                    'label'   => __('Download Products for Mapping'),
                    'title'   => __("Download listing's products file and use it for the mapping in your Mirakl back office"),
                    'href'    => $this->urlBuilder->getUrl(
                        self::LISTING_URL_PATH_DOWNLOAD,
                        ['id' => $item['id']]
                    ),
                ];

                if ($item['is_active']) {
                    $item[$name]['export_product_pending'] = [
                        'label'   => __('Export Pending Products'),
                        'title'   => __("This action will export the listing's products to Mirakl"),
                        'href'    => sprintf('%s#product_content', $this->urlBuilder->getUrl(
                            self::LISTING_URL_PATH_EXPORT_PRODUCT,
                            ['id' => $item['id'], 'export_mode' => strtolower(Listing::PRODUCT_MODE_PENDING)]
                        )),
                        'confirm' => [
                            'title' => __('Export Pending Products'),
                            'message' => __('Are you sure?'),
                        ],
                    ];

                    $item[$name]['export_product_error'] = [
                        'label'   => __('Export Products in Error'),
                        'title'   => __("This action will export the listing's products to Mirakl"),
                        'href'    => sprintf('%s#product_content', $this->urlBuilder->getUrl(
                            self::LISTING_URL_PATH_EXPORT_PRODUCT,
                            ['id' => $item['id'], 'export_mode' => strtolower(Listing::PRODUCT_MODE_ERROR)]
                        )),
                        'confirm' => [
                            'title' => __('Export Products in Error'),
                            'message' => __('Are you sure?'),
                        ],
                    ];

                    $item[$name]['export_product_all'] = [
                        'label'   => __('Export All Products'),
                        'title'   => __("This action will export the listing's products to Mirakl"),
                        'href'    => sprintf('%s#product_content', $this->urlBuilder->getUrl(
                            self::LISTING_URL_PATH_EXPORT_PRODUCT,
                            ['id' => $item['id'], 'export_mode' => strtolower(Listing::PRODUCT_MODE_ALL)]
                        )),
                        'confirm' => [
                            'title' => __('Export All Products'),
                            'message' => __('Are you sure?'),
                        ],
                    ];

                    $item[$name]['export_offer'] = [
                        'label'   => __('Export Prices & Stocks'),
                        'title'   => __("This action will export the listing's prices & stocks to Mirakl"),
                        'href'    => sprintf('%s#product_content', $this->urlBuilder->getUrl(
                            self::LISTING_URL_PATH_EXPORT_OFFER,
                            ['id' => $item['id']]
                        )),
                        'confirm' => [
                            'title' => __('Export Prices & Stocks'),
                            'message' => __('Are you sure?'),
                        ],
                    ];
                }

                $item[$name]['delete'] = [
                    'label'   => __('Delete'),
                    'href'    => $this->urlBuilder->getUrl(
                        self::LISTING_URL_PATH_DELETE,
                        ['id' => $item['id']]
                    ),
                    'confirm' => [
                        'title' => __('Delete'),
                        'message' => __('Are you sure?'),
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
