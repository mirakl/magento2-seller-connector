<?php
namespace MiraklSeller\Process\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use MiraklSeller\Process\Model\Process;
use MiraklSeller\Process\Model\ResourceModel\Process\CollectionFactory;

/**
 * This class is used to log some potential fatal errors occurring when executing processes.
 * Fatal errors cannot be handled easily to mark processes as STOPPED but we are able to retrieve the error and log it.
 */
class Error extends AbstractHelper
{
    const ERROR_FILE_PREFIX = 'process_error_';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param   Context             $context
     * @param   ApiHelper           $apiHelper
     * @param   Filesystem          $filesystem
     * @param   CollectionFactory   $collectionFactory
     */
    public function __construct(
        Context $context,
        ApiHelper $apiHelper,
        Filesystem $filesystem,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->apiHelper = $apiHelper;
        $this->filesystem = $filesystem;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Removes potential JSON error file associated with the specified process
     *
     * @param   Process $process
     * @return  bool
     */
    public function deleteProcessError(Process $process)
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        if (($filePath = $this->getProcessErrorFile($process)) && $varDir->isFile($filePath)) {
            return $varDir->delete($filePath);
        }

        return true;
    }

    /**
     * Returns the processes error path target
     *
     * @return  string|false
     */
    public function getErrorPath()
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $path = $varDir->getAbsolutePath() . DIRECTORY_SEPARATOR . 'mirakl' . DIRECTORY_SEPARATOR . 'process';

        if ($varDir->create($path)) {
            return false;
        }

        return $path;
    }

    /**
     * Returns the file path used for logging any error that would occurs when executing the specified process
     *
     * @param   Process $process
     * @return  string|false
     */
    public function getProcessErrorFile(Process $process)
    {
        if ($path = $this->getErrorPath()) {
            return $path . DIRECTORY_SEPARATOR . self::ERROR_FILE_PREFIX . $process->getId() . '.json';
        }

        return false;
    }

    /**
     * Returns error report associated with the specified process if any
     *
     * @param   Process $process
     * @return  array|false
     */
    public function getProcessErrorReport(Process $process)
    {
        $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (($filePath = $this->getProcessErrorFile($process)) && $varDir->isFile($filePath)) {
            $file = $varDir->openFile($filePath);

            return json_decode($file->readAll($file), true);
        }

        return false;
    }

    /**
     * Logs the specified error that occurs when executing the specified process
     *
     * @param   Process $process
     * @param   array   $error
     * @return  bool|int
     */
    public function logProcessError(Process $process, array $error)
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        if (($filePath = $this->getProcessErrorFile($process)) && $varDir->isFile($filePath)) {
            return $varDir->writeFile($filePath, json_encode($error, JSON_PRETTY_PRINT));
        }

        return false;
    }

    /**
     * Stops processes that are still running and that have an error file.
     * Returns the number of process stopped.
     *
     * @return  int
     */
    public function stopProcessesInError()
    {
        if ($path = $this->getErrorPath()) {
            $prefix = self::ERROR_FILE_PREFIX;
            $processIds = [];
            foreach (glob("$path/$prefix*.json", GLOB_NOSORT) as $file) {
                preg_match("/$prefix(\d+)\.json/", basename($file), $matches);
                if (isset($matches[1])) {
                    $processIds[] = $matches[1];
                }
            }

            if (!empty($processIds)) {
                $collection = $this->collectionFactory->create();
                $collection->addIdFilter($processIds);

                $stopCount = 0;
                foreach ($collection as $process) {
                    /** @var Process $process */
                    if ($error = $this->getProcessErrorReport($process)) {
                        $process->addOutput('db');
                        $process->output($error['message']);
                        if ($process->canStop()) {
                            $stopCount++;
                            $process->stop(Process::STATUS_STOPPED);
                        }
                        $this->deleteProcessError($process);
                    }
                }

                return $stopCount;
            }
        }

        return 0;
    }
}
