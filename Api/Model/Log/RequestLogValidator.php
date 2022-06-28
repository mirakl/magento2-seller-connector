<?php
namespace MiraklSeller\Api\Model\Log;

use Mirakl\Core\Request\RequestInterface;
use MiraklSeller\Api\Helper\Config;

class RequestLogValidator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Config  $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param   RequestInterface    $request
     * @return  string
     */
    private function getRequestUrl(RequestInterface $request)
    {
        $query = '';
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            $query = '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        }

        return 'api/' . urldecode($request->getUri() . $query);
    }

    /**
     * @param   RequestInterface    $request
     * @return  bool
     */
    public function validate(RequestInterface $request)
    {
        if (!$this->config->isApiLogEnabled()) {
            return false;
        }

        $filterPattern = $this->config->getApiLogFilter();

        if (empty($filterPattern)) {
            return true;
        }

        $filterPattern = '#' . trim($filterPattern, '#/') . '#i';
        $filterPattern = str_replace('/', '\/', $filterPattern);

        return 1 === preg_match($filterPattern, $this->getRequestUrl($request));
    }
}