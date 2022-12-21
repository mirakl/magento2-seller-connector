<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;

class ShowFile extends AbstractProcess
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     * @param Filesystem  $filesystem
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Filesystem $filesystem
    ) {
        parent::__construct(
            $context,
            $resultPageFactory
        );
        $this->filesystem = $filesystem;
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

        $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $path = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();
        $file = $varDir->openFile($path);

        if (pathinfo($path, PATHINFO_EXTENSION) === 'json') {
            // Show a JSON file
            $contents = json_decode($file->readAll(), true);
            $body = '<pre>' . htmlentities(json_encode($contents, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            // Try to show a CSV file
            $fgetcsv = function () use ($file) {
                return $file->readCsv( 0, ';', '"');
            };

            if (count($fgetcsv()) > 1) {
                // Parse CSV and show as HTML table
                $file->seek(0);
                $body = '<table border="1" cellpadding="2" style="border-collapse: collapse; border: 1px solid #aaa;">';
                while ($data = $fgetcsv()) {
                    $body .= sprintf('<tr>%s</tr>', implode('', array_map(function ($value) {
                        if (preg_match('#^(https?:\/\/.+)$#', $value)) {
                            $value = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
                        } else {
                            $value = htmlspecialchars($value);
                        }

                        return '<td>' . $value . '</td>';
                    }, $data)));
                }
                $body .= '</table>';
            } else {
                // Show raw contents
                $body = '<pre>' . htmlentities($file->readAll()) . '</pre>';
            }
        }

        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setContents($body);

        return $result;
    }
}
