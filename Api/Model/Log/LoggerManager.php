<?php
namespace MiraklSeller\Api\Model\Log;

use MiraklSeller\Api\Helper\Config;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerManager
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @param   Config  $config
     * @param   Logger  $logger
     * @param   string  $logFile
     */
    public function __construct(
        Config $config,
        Logger $logger,
        $logFile = '/var/log/mirakl_seller_api.log'
    ) {
        $this->config  = $config;
        $this->logger  = $logger;
        $this->logFile = $logFile;

        $this->initLogger();
    }

    /**
     * Clears log file contents
     *
     * @return  void
     */
    public function clear()
    {
        if ($this->getLogFileSize()) {
            file_put_contents($this->getLogFilePath(), '');
        }
    }

    /**
     * @return  string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * @return  string
     */
    public function getLogFileContents()
    {
        if (file_exists($this->getLogFilePath())) {
            return file_get_contents($this->getLogFilePath());
        }

        return '';
    }

    /**
     * @return  string
     */
    public function getLogFilePath()
    {
        return BP . $this->logFile;
    }

    /**
     * @return  int
     */
    public function getLogFileSize()
    {
        if (file_exists($this->getLogFilePath())) {
            return filesize($this->getLogFilePath());
        }

        return 0;
    }

    /**
     * @return  Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return  \GuzzleHttp\MessageFormatter
     */
    public function getMessageFormatter()
    {
        switch ($this->config->getApiLogOption()) {
            case LogOptions::LOG_REQUESTS_ONLY:
                $format = ">>>>>>>>\n{request}\n--------\n{error}";
                break;

            case LogOptions::LOG_ALL:
            default:
                $format = \GuzzleHttp\MessageFormatter::DEBUG;
        }

        return new \GuzzleHttp\MessageFormatter($format);
    }

    /**
     * @return  void
     */
    private function initLogger()
    {
        $this->logger->pushHandler(new StreamHandler($this->getLogFilePath()));
    }
}