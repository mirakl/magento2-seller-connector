<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Connection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use MiraklSeller\Api\Model\Connection as ConnectionModel;
use MiraklSeller\Api\Model\ResourceModel\Connection as ConnectionResource;

abstract class AbstractConnection extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Api::connections';

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @param   bool    $mustExists
     * @param   string  $id
     * @return  ConnectionModel
     * @throws  NotFoundException
     */
    protected function getConnection($mustExists = false, $id = null)
    {
        if (!$id) {
            $id = $this->getRequest()->getParam('id');
        }

        /** @var ConnectionModel $model */
        $model = $this->_objectManager->create(ConnectionModel::class);
        /** @var ConnectionResource $resourceModel */
        $resourceModel = $this->_objectManager->get(ConnectionResource::class);

        $resourceModel->load($model, $id);
        if ($mustExists && !$model->getId()) {
            $message = __('This connection no longer exists.');
            $this->messageManager->addErrorMessage($message);
            throw new NotFoundException($message);
        }

        return $model;
    }

    /**
     * @param   Page $resultPage
     * @return  Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Mirakl Seller'), __('Mirakl Seller'));

        return $resultPage;
    }
}
