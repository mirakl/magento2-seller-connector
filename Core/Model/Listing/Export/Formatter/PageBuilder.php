<?php
declare(strict_types=1);

namespace MiraklSeller\Core\Model\Listing\Export\Formatter;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Helper\Output;
use Magento\Framework\Exception\NoSuchEntityException;
use MiraklSeller\Core\Model\Listing;

class PageBuilder implements FormatterInterface
{
    /**
     * @var Output
     */
    private $output;

    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param Output $output
     * @param CatalogHelper $catalogHelper
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Output $output,
        CatalogHelper $catalogHelper,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->output = $output;
        $this->catalogHelper = $catalogHelper;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function format(array $data, Listing $listing)
    {
        foreach ($data as $code => $value) {
            try {
                $attribute = $this->getAttribute($code);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if (!empty($value)
                && $attribute->getExtensionAttributes()->getIsPagebuilderEnabled()
                && $attribute->getIsHtmlAllowedOnFront()
                && $attribute->getIsWysiwygEnabled()
                && $this->output->isDirectivesExists($value))
            {
                $data[$code] = $this->catalogHelper->getPageTemplateProcessor()->filter($value);
            }
        }

        return $data;
    }

    /**
     * @return ProductAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAttribute(string $code)
    {
        return $this->attributeRepository->get($code);
    }
}
