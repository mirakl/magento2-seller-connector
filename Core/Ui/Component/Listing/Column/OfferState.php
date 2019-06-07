<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MiraklSeller\Core\Model\Offer\State as OfferStateModel;

class OfferState extends Column
{
    /**
     * @var OfferStateModel
     */
    protected $offerStateModel;

    /**
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param OfferStateModel       $offerStateModel
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OfferStateModel $offerStateModel,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->offerStateModel = $offerStateModel;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $options = $this->offerStateModel->getOptions();
            $field = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$field]) && isset($options[$item[$field]])) {
                    $item[$this->getData('name')] = $options[$item[$field]];
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}
