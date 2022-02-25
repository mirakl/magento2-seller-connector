<?php
namespace MiraklSeller\Process\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Select;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use MiraklSeller\Process\Model\ResourceModel\Process as ProcessResource;

class HistoryClearer
{
    const FILES_DELETE_STEP              = 10000;
    const PROCESS_FILES_DIRECTORY_SUFFIX = 'mirakl/process';

    /**
     * @var ProcessResource
     */
    private $processResource;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param ProcessResource  $processResource
     * @param File             $file
     * @param Filesystem       $filesystem
     */
    public function __construct(
        ProcessResource $processResource,
        File $file,
        Filesystem $filesystem
    ) {
        $this->processResource = $processResource;
        $this->file = $file;
        $this->filesystem = $filesystem;
    }

    /**
     * Deletes processes and associated files created before $beforeDate
     *
     * @param  Process|null $process
     * @param  string|null  $beforeDate
     */
    public function execute(?Process $process = null, ?string $beforeDate = null)
    {
        try {
            if ($process && $beforeDate) {
                $process->output(__('Deleting all Mirakl processes and files created before %1...', $beforeDate), true);
            } else if ($process){
                $process->output(__('Deleting all Mirakl processes and files...'), true);
            }

            $this->deleteProcesses($beforeDate);

            if ($process) {
                $process->output(__('Done!'), true);
            }
        } catch (\Exception $e) {
            if ($process) {
                $process->output(__('An error occurred: %1', $e->getMessage()), true);
            } else {
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * Deletes all process files/directories created before $beforeDate
     *
     * @param string|null  $beforeDate
     * @throws \Exception
     */
    private function deleteProcesses(?string $beforeDate)
    {
        $connection = $this->processResource->getConnection();

        if (!$beforeDate) {
            // Remove all processes files and folders and truncate process table
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . self::PROCESS_FILES_DIRECTORY_SUFFIX;
            $this->file->rmdirRecursive($directory);
            $connection->truncateTable($this->processResource->getMainTable());

            return;
        }

        $tableName = $this->processResource->getMainTable();

        // Fetch processes step by step and delete associated files
        while (true) {
            $select = $connection->select()
                ->from(
                    ['t' => $tableName],
                    ['id', 'file', 'mirakl_file']
                )
                ->where('created_at < ?', $beforeDate)
                ->order('id ASC')
                ->limit(self::FILES_DELETE_STEP);

            $processes = $connection->fetchAll($select);

            if (!count($processes)) {
                break;
            }

            // We delete processes associated files
            $maxId = 0;
            foreach ($processes as $process) {
                $maxId = max($maxId, $process['id']);
                $file = $process['file'];
                if ($this->file->fileExists($file)) {
                    $this->file->rm($file);
                }
                $miraklFile = $process['mirakl_file'];
                if ($this->file->fileExists($miraklFile)) {
                    $this->file->rm($miraklFile);
                }
            }

            $select->reset(Select::LIMIT_COUNT);
            $select->reset(Select::ORDER);
            $select->where('id <= ?', $maxId);

            // We delete processed rows from database
            $delete = $connection->deleteFromSelect($select, 't');
            $connection->query($delete);
        }

        // Remove empty process files directories
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . self::PROCESS_FILES_DIRECTORY_SUFFIX;
        if (is_dir($directory)) {
            $this->removeEmptyFoldersRecursive($directory);
        }
    }

    /**
     * Delete empty sub folders recursively
     *
     * @param string $directory
     * @return bool
     */
    private function removeEmptyFoldersRecursive(string $directory)
    {
        $empty = true;

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') as $path) {
            if (is_dir($path)) {
                if (!$this->removeEmptyFoldersRecursive($path)) {
                    $empty = false;
                }
            } else {
                $empty = false;
            }
        }

        if ($empty) {
            $this->file->rmdir($directory);
        }

        return $empty;
    }
}