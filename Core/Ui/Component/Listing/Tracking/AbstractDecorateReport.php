<?php
namespace MiraklSeller\Core\Ui\Component\Listing\Tracking;

use Magento\Ui\Component\Listing\Columns\Column;

abstract class AbstractDecorateReport extends Column
{
    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->decorateReport($item, $fieldName);
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }

    /**
     * @param   array   $tracking
     * @param   string  $field
     * @return  string
     */
    public function decorateReport($tracking, $field)
    {
        $html = '';

        if ($tracking[$field]) {
            $downloadUrl = $this->getUrl('mirakl_seller/tracking/downloadReport', [
                'type' => $this->getTrackingType(),
                'id' => $tracking['id'],
                'field' => $field,
            ]);

            $html = sprintf(
                '<a href="%s" title="%s">%s</a>',
                $downloadUrl,
                __('Download report (CSV)'),
                __('Download')
            );
        }

        return $html;
    }

    /**
     * @return  string
     */
    abstract protected function getTrackingType();

    /**
     * Retrieve url
     *
     * @param   string  $route
     * @param   array   $params
     * @return  string
     */
    protected function getUrl($route, $params = [])
    {
        return $this->getContext()->getUrl($route, $params);
    }
}