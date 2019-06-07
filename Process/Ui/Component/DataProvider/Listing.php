<?php
namespace MiraklSeller\Process\Ui\Component\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use MiraklSeller\Process\Helper\Data as ProcessHelper;
use MiraklSeller\Process\Model\Process as Process;
use MiraklSeller\Process\Model\ProcessFactory as ProcessFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ResourceFactory;

class Listing extends DataProvider
{
    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param   string                  $name
     * @param   string                  $primaryFieldName
     * @param   string                  $requestFieldName
     * @param   ReportingInterface      $reporting
     * @param   SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param   RequestInterface        $request
     * @param   FilterBuilder           $filterBuilder
     * @param   ProcessHelper           $processHelper
     * @param   ProcessFactory          $processFactory
     * @param   ResourceFactory         $resourceFactory
     * @param   UrlInterface            $urlBuilder
     * @param   array                   $meta
     * @param   array                   $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        ProcessHelper $processHelper,
        ProcessFactory $processFactory,
        ResourceFactory $resourceFactory,
        UrlInterface $urlBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting,
            $searchCriteriaBuilder,$request, $filterBuilder, $meta, $data);

        $this->processHelper = $processHelper;
        $this->processFactory = $processFactory;
        $this->resourceFactory = $resourceFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = parent::getData();

        if (!isset($data['items'])) {
            return $data;
        }

        $resource = $this->resourceFactory->create();

        foreach ($data['items'] as & $item) {
            $process = $this->processFactory->create();
            $resource->load($process, $item['id']);

            $item['parent_id'] = $this->decorateParent($process);
            $item['created_at'] = $this->decorateCreatedAt($process->getCreatedAt());
            $item['updated_at'] = $this->decorateCreatedAt($process->getCreatedAt());
            $item['duration'] = $this->decorateDuration($process);
            $item['file'] = $this->decorateFile('file', $process);
            $item['output'] = $this->decorateOutput($process);
            $item['status'] = $this->decorateStatus('status', $process);
            $item['mirakl_file'] = $this->decorateFile('mirakl_file', $process);
            $item['mirakl_status'] = $this->decorateStatus('status', $process);
        }

        return $data;
    }

    /**
     * @param   string|\DateTime   $createdAt
     * @return  string
     */
    protected function decorateCreatedAt($createdAt)
    {
        if (is_string($createdAt)) {
            $createdAt = new \DateTime($createdAt);
        }

        return sprintf(
            '<span class="nobr">%s<br/>(%s)</span>',
            $createdAt->format('Y-m-d H:i:s'),
            __('%1 ago', $this->processHelper->getMoment($createdAt))
        );
    }

    /**
     * @param   Process $process
     * @return  string
     */
    public function decorateDuration(Process $process)
    {
        return $this->processHelper->formatDuration($process->getDuration());
    }

    /**
     * @param   string  $column
     * @param   Process $process
     * @return  string
     */
    public function decorateFile($column, Process $process)
    {
        $isMirakl = strstr($column, 'mirakl') === false ? false : true;
        $html = '';
        if ($fileSize = $process->getFileSizeFormatted('&nbsp;', $isMirakl)) {
            $html = sprintf(
                '<a href="%s">%s</a>&nbsp;(%s)',
                $this->getUrl('mirakl_seller/process/downloadFile', ['id' => $process->getId(), 'mirakl' => $isMirakl ? '1' : '0']),
                __('Download'),
                $fileSize
            );
            if ($process->canShowFile($isMirakl)) {
                $html .= sprintf(
                    '<br/> %s <a target="_blank" href="%s" title="%s">%s</a>',
                    __('or'),
                    $this->getUrl('mirakl_seller/process/showFile', ['id' => $process->getId()]),
                    __('Open in Browser'),
                    __('open in browser')
                );
            }
        }

        return $html;
    }

    /**
     * @param   Process $process
     * @return  string
     */
    public function decorateParent($process)
    {
        if (!$process->getParentId()) {
            return '-';
        }

        $url = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getUrl('mirakl_seller/process/view', ['id' => $process->getParentId()]),
            __('View Parent Process'),
            $process->getParentId()
        );

        return $url;
    }

    /**
     * @param   Process $process
     * @return  string
     */
    public function decorateOutput(Process $process)
    {
        $value = $process->getOutput();
        if (strlen($value)) {
            $lines = array_slice(explode("\n", $value), 0, 6);
            if (count($lines) === 6) {
                $lines[5] = '...';
            }
            array_walk($lines, function(&$line) {
                $line = $this->processHelper->truncate($line, 80);
            });
            $value = implode('<br/>', $lines);
        }

        return $value;
    }

    /**
     * @param   string  $column
     * @param   Process $process
     * @return  string
     */
    public function decorateStatus($column, $process)
    {
        if (!$process->getStatus()) return '';

        $isMirakl = strstr($column, 'mirakl') === false ? false : true;

        return '<span class="' . $process->getStatusClass($isMirakl) . '"><span>' . __($process->getStatus()) . '</span></span>';
    }

    /**
     * Retrieve url
     *
     * @param   string  $route
     * @param   array   $params
     * @return  string
     */
    protected function getUrl($route, $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}