<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use MiraklSeller\Core\Model\Listing as ListingModel;
use MiraklSeller\Core\Model\ResourceModel\Listing as ListingResource;

abstract class AbstractListing extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Core::listings';

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
     * @return  ListingModel
     * @throws  NotFoundException
     */
    protected function getListing($mustExists = false, $id = null)
    {
        if (!$id) {
            $id = $this->getRequest()->getParam('id');
        }

        /** @var ListingModel $model */
        $model = $this->_objectManager->create(ListingModel::class);
        /** @var ListingResource $resourceModel */
        $resourceModel = $this->_objectManager->get(ListingResource::class);

        $resourceModel->load($model, $id);
        if ($mustExists && !$model->getId()) {
            $message = __('This listing no longer exists.');
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
