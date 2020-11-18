<?php
namespace MiraklSeller\Process\Model;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use MiraklSeller\Process\Helper\Config as ProcessConfig;
use MiraklSeller\Process\Helper\Data as ProcessHelper;
use MiraklSeller\Process\Helper\Error as ErrorHelper;
use MiraklSeller\Process\Model\Output\Factory as OutputFactory;
use MiraklSeller\Process\Model\Output\OutputInterface;
use MiraklSeller\Process\Model\Process as ProcessModel;
use MiraklSeller\Process\Model\ProcessFactory as ProcessModelFactory;
use MiraklSeller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use MiraklSeller\Process\Model\ResourceModel\Process\Collection as ProcessCollection;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory as ProcessCollectionFactory;

/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  $this   setDuration(int $duration)
 * @method  string  getFile()
 * @method  $this   setFile(string $file)
 * @method  string  getHash()
 * @method  $this   setHash(string $hash)
 * @method  string  getHelper()
 * @method  $this   setHelper(string $helper)
 * @method  string  getMethod()
 * @method  $this   setMethod(string $method)
 * @method  string  getMiraklFile()
 * @method  $this   setMiraklFile(string $file)
 * @method  string  getMiraklStatus()
 * @method  $this   setMiraklStatus(string $status)
 * @method  string  getSuccessReport()
 * @method  $this   setSuccessReport(string $report)
 * @method  string  getSynchroId()
 * @method  $this   setSynchroId(string $synchroId)
 * @method  string  getMiraklType()
 * @method  $this   setMiraklType(string $type)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  string  getOutput()
 * @method  $this   setOutput(string $output)
 * @method  int     getParentId()
 * @method  $this   setParentId(int $parentId)
 * @method  $this   setParams(string|array $params)
 * @method  string  getStatus()
 * @method  $this   setStatus(string $status)
 * @method  string  getType()
 * @method  $this   setType(string $type)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 */
class Process extends AbstractModel
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_IDLE       = 'idle';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_STOPPED    = 'stopped';
    const STATUS_TIMEOUT    = 'timeout';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_ERROR      = 'error';

    const TYPE_API          = 'API';
    const TYPE_CLI          = 'CLI';
    const TYPE_CRON         = 'CRON';
    const TYPE_ADMIN        = 'ADMIN';
    const TYPE_IMPORT       = 'IMPORT';

    /**
     * @var string
     */
    protected $_eventPrefix = 'mirakl_process';

    /**
     * @var string
     */
    protected $_eventObject = 'process';

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var OutputInterface[]
     */
    protected $outputs = [];

    /**
     * @var bool
     */
    protected $_running = false;

    /**
     * @var float
     */
    protected $startedAt;

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var ErrorHelper
     */
    private $errorHelper;

    /**
     * @var ProcessHelper
     */
    private $processHelper;

    /**
     * @var ProcessConfig
     */
    private $processConfig;

    /**
     * @var ProcessModelFactory
     */
    private $processModelFactory;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @var ProcessCollectionFactory
     */
    private $processCollectionFactory;

    /**
     * @var OutputFactory
     */
    private $outputFactory;

    /**
     * @var string
     */
    protected $decodeMethod = 'unserialize';

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   AbstractResource|null       $resource
     * @param   AbstractDbCollection|null   $resourceCollection
     * @param   ObjectManagerInterface      $objectManager
     * @param   UrlInterface                $urlBuilder
     * @param   ApiHelper                   $apiHelper
     * @param   ErrorHelper                 $errorHelper
     * @param   ProcessHelper               $processHelper
     * @param   ProcessConfig               $processConfig
     * @param   ProcessModelFactory         $processModelFactory
     * @param   ProcessResourceFactory      $processResourceFactory
     * @param   ProcessCollectionFactory    $processCollectionFactory
     * @param   OutputFactory               $outputFactory
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ObjectManagerInterface $objectManager,
        UrlInterface $urlBuilder,
        ApiHelper $apiHelper,
        ErrorHelper $errorHelper,
        ProcessHelper $processHelper,
        ProcessConfig $processConfig,
        ProcessModelFactory $processModelFactory,
        ProcessResourceFactory $processResourceFactory,
        ProcessCollectionFactory $processCollectionFactory,
        OutputFactory $outputFactory,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->objectManager = $objectManager;
        $this->urlBuilder = $urlBuilder;
        $this->apiHelper = $apiHelper;
        $this->errorHelper = $errorHelper;
        $this->processHelper = $processHelper;
        $this->processConfig = $processConfig;
        $this->processModelFactory = $processModelFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->processCollectionFactory = $processCollectionFactory;
        $this->outputFactory = $outputFactory;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Process::class);

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if ($errno == E_USER_ERROR) {
                $this->handleError($errstr, $errfile, $errline);
            }
        });

        register_shutdown_function(function() {
            $error = error_get_last();
            if (!empty($error) && $error['type'] == E_ERROR) {
                $this->errorHelper->logProcessError($this, $error);
                $this->handleError($error['message'], $error['file'], $error['line']);
            }
        });
    }

    /**
     * @param   string  $str
     * @return  mixed
     */
    protected function decode($str)
    {
        return call_user_func($this->decodeMethod, $str);
    }

    /**
     * @param   string  $errstr
     * @param   string  $errfile
     * @param   int     $errline
     * @throws  \ErrorException
     */
    protected function handleError($errstr, $errfile, $errline)
    {
        $message = sprintf('%s in %s on line %d', $errstr, $errfile, $errline);
        throw new \ErrorException($message);
    }

    /**
     * Saves potential uncatched messages in current process and stop it if necessary
     */
    public function __destruct()
    {
        if ($this->_running) {
            if ($output = ob_get_contents()) {
                $this->output($output);
            }
            $this->fail(); // Process has been started but not stopped, an error occurred
        }
    }

    /**
     * @param   string|OutputInterface  $output
     * @return  $this
     * @throws  \Exception
     */
    public function addOutput($output)
    {
        if (is_string($output)) {
            $output = $this->outputFactory->create($output, $this);
        }

        if (!$output instanceof OutputInterface) {
            throw new \Exception('Invalid output specified.');
        }

        $this->outputs[$output->getType()] = $output;

        return $this;
    }

    /**
     * Marks current process as cancelled and stops execution
     *
     * @param   string|null $message
     * @return  $this
     */
    public function cancel($message = null)
    {
        if ($message) {
            $this->output($message);
        }

        $this->stop(self::STATUS_CANCELLED);

        $this->getChildrenCollection()->walk('cancel', ['Cancelled because parent has been cancelled']);

        return $this;
    }

    /**
     * Returns true if process can be ran
     *
     * @return  bool
     */
    public function canRun()
    {
        $parent = $this->getParent();

        return !$this->isProcessing() && !$this->isStatusIdle() && (!$parent || $parent->isCompleted());
    }

    /**
     * @param   bool    $isMirakl
     * @return  bool
     */
    public function canShowFile($isMirakl = false)
    {
        $fileSize = $this->getFileSize($isMirakl);

        return $fileSize <= ($this->processConfig->getShowFileMaxSize() * 1024 * 1024); // in MB
    }

    /**
     * Returns true if process can be set to STOPPED status
     *
     * @return  bool
     */
    public function canStop()
    {
        return $this->isProcessing();
    }

    /**
     * Calls current process helper->method()
     *
     * @throws  \RuntimeException
     * @throws  \InvalidArgumentException
     */
    public function execute()
    {
        $this->start();

        try {
            $this->_running = true;

            $this->errorHelper->deleteProcessError($this);

            set_time_limit(0);
            ini_set('memory_limit', -1);

            ob_start();

            if ($this->isProcessing()) {
                throw new \RuntimeException('Process is already running.');
            }

            $this->setStatus(self::STATUS_PROCESSING);

            $helper = $this->getHelperInstance();
            $method = $this->getMethod();

            if (!method_exists($helper, $method)) {
                throw new \InvalidArgumentException("Invalid helper method specified '$method'");
            }

            $this->output(__('Running %1::%2()', get_class($helper), $method), true);
            $args = [$this];
            if ($this->getParams()) {
                $args = array_merge($args, $this->getParams());
            }

            call_user_func_array([$helper, $method], $args);

            $this->stop();

        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        } finally {
            if ($output = ob_get_clean()) {
                $this->output($output, true);
            }
            $this->_running = false;
        }
    }

    /**
     * Marks current process as failed and stops execution
     *
     * @param   string|null $message
     * @return  $this
     */
    public function fail($message = null)
    {
        if ($message) {
            $this->output($message);
        }

        $this->stop(self::STATUS_ERROR);

        $this->getChildrenCollection()->walk('cancel', ['Cancelled because parent has failed']);

        return $this;
    }

    /**
     * @return  ProcessCollection
     */
    public function getChildrenCollection()
    {
        return $this->processCollectionFactory->create()->addParentFilter($this->getId());
    }

    /**
     * Returns process file download URL for admin
     *
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getDownloadFileUrl($isMirakl = false)
    {
        $file = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (!$file || !file_exists($file)) {
            return false;
        }

        return $this->urlBuilder->getUrl('mirakl/process/downloadFile', [
            'id' => $this->getId(),
            'mirakl' => $isMirakl,
        ]);
    }

    /**
     * @return  int|\DateInterval
     */
    public function getDuration()
    {
        $duration = $this->_getData('duration');
        if (!$duration) {
            if ($this->isProcessing() || $this->isStatusIdle()) {
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $duration = $start->diff(new \DateTime());
            } elseif ($this->isEnded()){
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $end = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getUpdatedAt());
                $duration = $start->diff($end);
            }
        }

        return $duration;
    }

    /**
     * @return  array|false
     */
    public function getErrorReport()
    {
        return $this->errorHelper->getProcessErrorReport($this);
    }

    /**
     * Returns file size in bytes
     *
     * @param   bool    $isMirakl
     * @return  bool|int
     */
    public function getFileSize($isMirakl = false)
    {
        $filePath = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (strlen($filePath) && is_file($filePath)) {
            return filesize($filePath);
        }

        return false;
    }

    /**
     * Returns file size formatted
     *
     * @param   string  $separator
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileSizeFormatted($separator = ' ', $isMirakl = false)
    {
        if ($fileSize = $this->getFileSize($isMirakl)) {
            return $this->processHelper->formatSize($fileSize, $separator);
        }

        return false;
    }

    /**
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileUrl($isMirakl = false)
    {
        $file = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (!$file || !file_exists($file)) {
            return false;
        }

        return $this->processHelper->getFileUrl($file);
    }

    /**
     * @return  mixed
     * @throws  \InvalidArgumentException
     */
    private function getHelperInstance()
    {
        $name = $this->getHelper();
        if (!class_exists($name)) {
            throw new \InvalidArgumentException("Invalid helper specified '$name'");
        }

        return $this->objectManager->create($name);
    }

    /**
     * @return  array
     */
    public function getParams()
    {
        $params = $this->_getData('params');
        if (is_string($params)) {
            $params = $this->decode($params);
        }

        return is_array($params) ? $params : [];
    }

    /**
     * @return  ProcessModel|null
     */
    public function getParent()
    {
        if (!$this->getParentId()) {
            return null;
        }

        $process = $this->processModelFactory->create();
        $this->processResourceFactory->create()->load($process, $this->getParentId());

        return $process;
    }

    /**
     * @return  array|null
     */
    public static function getStatuses()
    {
        static $statuses;
        if (!$statuses) {
            $class = new \ReflectionClass(__CLASS__);
            foreach ($class->getConstants() as $name => $value) {
                if (0 === strpos($name, 'STATUS_')) {
                    $statuses[$value] = $value;
                }
            }
        }

        return $statuses;
    }

    /**
     * @param   bool    $isMirakl
     * @return  string
     */
    public function getStatusClass($isMirakl = false)
    {
        $status = $isMirakl ? $this->getMiraklStatus() : $this->getStatus();

        switch ($status) {
            case self::STATUS_PENDING:
            case self::STATUS_IDLE:
                $class = 'grid-severity-minor';
                break;
            case self::STATUS_PROCESSING:
                $class = 'grid-severity-major';
                break;
            case self::STATUS_STOPPED:
            case self::STATUS_ERROR:
            case self::STATUS_TIMEOUT:
                $class = 'grid-severity-critical';
                break;
            case self::STATUS_COMPLETED:
            default:
                $class = 'grid-severity-notice';
        }

        return $class;
    }

    /**
     * @return  array|null
     */
    public static function getTypes()
    {
        static $types;
        if (!$types) {
            $class = new \ReflectionClass(__CLASS__);
            foreach ($class->getConstants() as $name => $value) {
                if (0 === strpos($name, 'TYPE_')) {
                    $types[$value] = $value;
                }
            }
        }

        return $types;
    }

    /**
     * Returns process URL for admin
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->urlBuilder->getUrl('mirakl_seller/process/view', [
            'id' => $this->getId()
        ]);
    }

    /**
     * Sets current process status to idle
     *
     * @return  $this
     */
    public function idle()
    {
        return $this->setStatus(self::STATUS_IDLE);
    }

    /**
     * @return  bool
     */
    public function isCancelled()
    {
        return $this->getStatus() == self::STATUS_CANCELLED;
    }

    /**
     * @return  bool
     */
    public function isCompleted()
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * @return  bool
     */
    public function isEnded()
    {
        return $this->isCompleted() || $this->isStopped() || $this->isTimeout()
            || $this->isCancelled() || $this->isError();
    }

    /**
     * @return  bool
     */
    public function isError()
    {
        return $this->getStatus() === self::STATUS_ERROR;
    }

    /**
     * @return  bool
     */
    public function isPending()
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    /**
     * @return  bool
     */
    public function isProcessing()
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    /**
     * @return  bool
     */
    public function isStatusIdle()
    {
        return $this->getStatus() === self::STATUS_IDLE;
    }

    /**
     * @return  bool
     */
    public function isStopped()
    {
        return $this->getStatus() === self::STATUS_STOPPED;
    }

    /**
     * @return  bool
     */
    public function isTimeout()
    {
        return $this->getStatus() === self::STATUS_TIMEOUT;
    }

    /**
     * Outputs specified string in all associated output handlers
     *
     * @param   string  $str
     * @param   bool    $save
     * @return  $this
     */
    public function output($str, $save = false)
    {
        foreach ($this->outputs as $output) {
            $output->display($str);
        }

        if ($save) {
            $this->processResourceFactory->create()->save($this);
        }

        return $this;
    }

    /**
     * Wraps process execution
     *
     * @param   bool    $force
     * @return  $this
     * @throws \Exception
     */
    public function run($force = false)
    {
        if (!$this->isPending() && !$force) {
            throw new \Exception('Cannot run a process that is not in pending status.');
        }

        $parent = $this->getParent();
        if ($parent && !$parent->isCompleted()) {
            throw new \Exception("Parent process #{$parent->getId()} has not completed yet.");
        }

        $this->execute();

        return $this;
    }

    /**
     * Starts current process
     *
     * @return  $this
     */
    public function start()
    {
        if (!$this->startedAt) {
            $this->startedAt = microtime(true);
            $this->setCreatedAt(date('Y-m-d H:i:s'))
                ->addOutput('db')
                ->setOutput(null)
                ->setDuration(null);

            if (PHP_SAPI == 'cli') {
                $this->addOutput('cli');
            }

            $this->processResourceFactory->create()->save($this);
        }

        return $this;
    }

    /**
     * Stops current process
     *
     * @param   string  $status
     * @return  $this
     */
    public function stop($status = self::STATUS_COMPLETED)
    {
        $this->updateDuration();
        $this->setStatus($status);

        // Closing all outputs
        foreach ($this->outputs as $output) {
            $output->close();
        }

        $this->processResourceFactory->create()->save($this);

        restore_error_handler();

        return $this;
    }

    /**
     * Updates current process duration
     *
     * @return  $this
     */
    public function updateDuration()
    {
        if ($this->startedAt) {
            $duration = ceil(microtime(true) - $this->startedAt);
            $this->setDuration($duration);
        } elseif ($this->getCreatedAt()) {
            $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
            $duration = (new \DateTime())->getTimestamp() - $start->getTimestamp();
            $this->setDuration(max(1, $duration)); // 1 second minimum
        }

        return $this;
    }
}
