<?php
namespace MiraklSeller\Sales\Ui\Component\Order\Items\Column;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ProductImage extends \Magento\Ui\Component\Listing\Columns\Column
{
    const IMAGE_TYPE  = 'product_listing_thumbnail';

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @param   ContextInterface   $context
     * @param   UiComponentFactory $uiComponentFactory
     * @param   ImageHelper        $imageHelper
     * @param   array              $components
     * @param   array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ImageHelper $imageHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->imageHelper = $imageHelper;
    }

    /**
     * @param   array   $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = '';

                if (!$product = $item['product']) {
                    continue;
                }

                if ($imageUrl = $this->getProductImageUrl($product)) {
                    $item[$fieldName] = sprintf('<img src="%s" alt="" />', $imageUrl);
                }
            }
        }

        return $dataSource;
    }

    protected function getProductImageUrl(ProductInterface $product)
    {
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            return $this->imageHelper
                ->init($product, self::IMAGE_TYPE)
                ->getUrl();
        } catch (\Exception $e) {
            // Ignore any exception on image
        }

        return '';
    }
}