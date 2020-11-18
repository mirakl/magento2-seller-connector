<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Product\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Process\Helper\Data as ProcessHelper;

class ImportMessage extends Column
{
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param Escaper               $escaper
     * @param ProcessHelper         $processHelper
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        ProcessHelper $processHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->escaper = $escaper;
        $this->processHelper = $processHelper;
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
                    $item[$fieldName] = $this->decorateMessage($item[$fieldName]);
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }

    /**
     * @param   string  $value
     * @return  string
     */
    public function decorateMessage($value)
    {
        $shortValue = $value;
        if (strlen($value)) {
            $shortValue = $this->processHelper->truncate($value, 75);
        }

        return sprintf(
            '<span title="%s">%s</span>',
            $this->escaper->escapeHtml($value),
            $shortValue
        );
    }
}