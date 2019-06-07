<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use MiraklSeller\Core\Model\Listing;

class ProductExport extends AbstractExport
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $mode = strtoupper($this->getRequest()->getParam('export_mode'));

        if (!in_array($mode, Listing::getAllowedProductModes())) {
            $this->messageManager->addErrorMessage(__('This mode is not supported'));
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath(
                '*/*/edit',
                ['id' => $this->getRequest()->getParam('id')]
            );
        }

        return $this->_exportAction(Listing::TYPE_PRODUCT, true, $mode);
    }
}