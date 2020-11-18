<?php
namespace MiraklSeller\Core\Block\Adminhtml\Listing\Edit;

use Magento\Backend\Block\Template;

class ExportProduct extends Template
{
    /**
     * @param   string  $mode
     * @return  string
     */
    public function getExportProductUrl($mode = 'PLACEHOLDER')
    {
        return $this->getUrl('*/*/productExport', [
            'id' => $this->_request->getParam('id'),
            'export_mode' => $mode,
        ]);
    }
}
