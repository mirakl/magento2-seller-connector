<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Tracking;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;

class DownloadReport extends AbstractTracking
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $tracking = $this->getTracking();

        if (!$tracking->getId()) {
            return $this->redirectError(__('This tracking no longer exists.'));
        }

        $field = $this->getRequest()->getParam('field');

        if (!$contents = $tracking->getData($field)) {
            return $this->redirectError(__('The report does not exist.'), true);
        }

        $fileName = sprintf('tracking_%d_%s.csv', $tracking->getId(), $field);

        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Length', strlen($contents))
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $result->setContents($contents);

        $this->_session->writeClose();

        return $result;
    }
}
