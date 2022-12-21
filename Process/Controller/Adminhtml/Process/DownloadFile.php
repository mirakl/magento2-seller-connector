<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;
use MiraklSeller\Process\Helper\Data as ProcessHelper;

class DownloadFile extends AbstractProcess
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @param Context       $context
     * @param PageFactory   $resultPageFactory
     * @param Filesystem    $filesystem
     * @param ProcessHelper $processHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Filesystem $filesystem,
        ProcessHelper $processHelper
    ) {
        parent::__construct($context, $resultPageFactory);
        $this->filesystem = $filesystem;
        $this->processHelper = $processHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        $path = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();
        if (!$path) {
            return $this->redirectError(__('File does not exist.'), true);
        }

        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $file = $directory->openFile($path);

        $fileName = pathinfo($path, PATHINFO_BASENAME);

        $this->getResponse()->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0',true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Length', $this->processHelper->getFileSize($path))
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        $this->_session->writeClose();
        print_r($file->readAll());
    }
}
