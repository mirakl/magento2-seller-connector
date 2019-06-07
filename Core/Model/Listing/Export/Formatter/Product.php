<?php
namespace MiraklSeller\Core\Model\Listing\Export\Formatter;

use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Core\Helper\Listing\Product as Helper;
use MiraklSeller\Core\Model\Listing;

class Product implements FormatterInterface
{
    /**
     * Custom field in order to allow seller to map this field with the variant group code field in Mirakl
     */
    const VARIANT_GROUP_CODE_FIELD = 'variant_group_code';
    const CATEGORY_FIELD = 'category';
    const IMAGE_FIELD = 'image_';

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Helper  $helper
     * @param   Config  $config
     */
    public function __construct(Helper $helper, Config $config)
    {
        $this->helper = $helper;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $data, Listing $listing)
    {
        $category = '';
        if (isset($data['category_paths']) && !empty($data['category_paths'])) {
            $path = $this->helper->getCategoryFromPaths($data['category_paths']);
            $category = implode('/', str_replace('/', '-', $path));
        }

        $parentSku = '';
        if (isset($data['parent_sku'])) {
            $parentSku = $data['parent_sku'];
        }

        $formatData = array_intersect_key(
            $data,
            array_fill_keys($listing->getConnection()->getExportableAttributes(), null)
        );

        $nbImageToExport = $this->config->getNumberImageMaxToExport();

        // We must add the column key definition for array_merge
        for ($i = 0; $i < $nbImageToExport; $i++) {
            $formatData[self::IMAGE_FIELD . ($i + 1)] = '';
        }

        foreach ($data as $key => $item) {
            if (strstr($key, self::IMAGE_FIELD) !== false) {
                $formatData[$key] = $data[$key];
            }
        }

        $formatData[self::CATEGORY_FIELD] = $category;
        $formatData[self::VARIANT_GROUP_CODE_FIELD] = $parentSku;

        return $formatData;
    }
}