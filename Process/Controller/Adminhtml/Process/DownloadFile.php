<?php
namespace MiraklSeller\Process\Controller\Adminhtml\Process;

use MiraklSeller\Process\Controller\Result\Download;

class DownloadFile extends AbstractProcess
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        $file = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();
        if (!$file) {
            return $this->redirectError(__('File does not exist.'), true);
        }

        $fileName = pathinfo($file, PATHINFO_BASENAME);

        /** @var Download $result */
        $result = $this->resultFactory->create(Download::TYPE_DOWNLOAD);
        $result->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $result->setFile($file);

        $this->_session->writeClose();

        return $result;
    }
}
