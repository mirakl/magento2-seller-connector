<?php
namespace MiraklSeller\Process\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory;

class Data extends AbstractHelper
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param   Context             $context
     * @param   ApiHelper           $apiHelper
     * @param   CollectionFactory   $collectionFactory
     *
     */
    public function __construct(Context $context, ApiHelper $apiHelper, CollectionFactory $collectionFactory)
    {
        parent::__construct($context);
        $this->apiHelper = $apiHelper;
        $this->collectionFactory =  $collectionFactory;
    }

    /**
     * Format specified duration (in seconds) into human readable duration
     *
     * @param   int|\DateInterval    $duration
     * @return  string
     */
    public function formatDuration($duration)
    {
        if (!$duration) {
            return '';
        }

        if ($duration instanceof \DateInterval) {
            $days    = $duration->d;
            $hours   = $duration->h;
            $minutes = $duration->i;
            $seconds = $duration->s;
        } else {
            $days      = floor($duration / 86400);
            $duration -= $days * 86400;
            $hours     = floor($duration / 3600);
            $duration -= $hours * 3600;
            $minutes   = floor($duration / 60);
            $seconds   = floor($duration - $minutes * 60);
        }

        $duration = '';
        if ($days > 0) {
            $duration .= __('%1d', $days) . ' ';
        }
        if ($hours > 0) {
            $duration .= __('%1h', $hours) . ' ';
        }
        if ($minutes > 0) {
            $duration .= __('%1m', $minutes) . ' ';
        }
        if ($seconds > 0) {
            $duration .= __('%1s', $seconds);
        }

        return trim($duration);
    }

    /**
     * Formats given size (in bytes) into an easy readable size
     *
     * @param   int     $size
     * @param   string  $separator
     * @return  string
     */
    public function formatSize($size, $separator = ' ')
    {
        return $this->apiHelper->formatSize($size, $separator);
    }

    /**
     * Returns number of seconds between now and given date, formatted into readable duration if needed
     *
     * @param   \DateTime   $date
     * @param   bool        $toDuration
     * @return  int|string
     */
    public function getMoment(\DateTime $date, $toDuration = true)
    {
        $now = new \DateTime();

        if ($toDuration) {
            return $this->formatDuration($now->diff($date));
        }

        return $now->getTimestamp() - $date->getTimestamp();
    }

    /**
     * Returns URL to the specified file
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getFileUrl($filePath)
    {
        $relativePath = $this->getRelativePath($filePath);
        $baseUrl = $this->apiHelper->getBaseUrl();

        return $baseUrl . $relativePath;
    }

    /**
     * Returns the older pending process
     *
     * @return  null|Process
     */
    public function getPendingProcess()
    {
        $process = null;

        // Retrieve processing processes
        $processing = $this->collectionFactory->create()
            ->addProcessingFilter();

        // Retrieve pending processes
        $pending = $this->collectionFactory->create()
            ->addPendingFilter()
            ->addExcludeHashFilter($processing->getColumnValues('hash'))
            ->addParentCompletedFilter()
            ->setOrder('id', 'ASC'); // oldest first

        $pending->getSelect()->limit(1);

        if ($pending->count()) {
            $process = $pending->getFirstItem();
        }

        return $process;
    }

    /**
     * Removes base dir from specified file path
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getRelativePath($filePath)
    {
        return trim(str_replace(BP, '', $filePath), DIRECTORY_SEPARATOR);
    }

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param   string  $string The string to check its length
     * @return  int              The length of the string
     */
    public function strlen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * Truncates a message to the given length.
     *
     * @param   string  $message
     * @param   int     $length
     * @param   string  $suffix
     * @return  string
     */
    public function truncate($message, $length, $suffix = '...')
    {
        $computedLength = $length - $this->strlen($suffix);

        if ($computedLength > $this->strlen($message)) {
            return $message;
        }

        if (false === $encoding = mb_detect_encoding($message, null, true)) {
            return substr($message, 0, $length).$suffix;
        }

        return mb_substr($message, 0, $length, $encoding).$suffix;
    }
}
