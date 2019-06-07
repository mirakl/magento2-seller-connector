<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Listing;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Validator\Exception;
use MiraklSeller\Core\Helper\Listing as ListingHelper;
use MiraklSeller\Core\Model\ResourceModel\Listing;

class Save extends Refresh
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param   Context                 $context
     * @param   Registry                $coreRegistry
     * @param   ListingHelper           $listingHelper
     * @param   DataPersistorInterface  $dataPersistor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ListingHelper $listingHelper,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $coreRegistry, $listingHelper);
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data && isset($data['main'])) {
            $data = $this->flatData($data);

            $model = $this->getListing(false, isset($data['id']) ? $data['id'] : null);
            $model->setData($data);

            $model->setBuilderParams($model->getBuilder()->getBuilderParams($data));

            if (isset($data['additional_fields'])) {
                $model->setOfferAdditionalFieldsValues($data['additional_fields']);
            }

            $this->_eventManager->dispatch(
                'mirakl_seller_listing_prepare_save',
                ['listing' => $model, 'request' => $this->getRequest()]
            );  

            try {
                $model->validateBeforeSave();
            } catch (Exception $e) {
                foreach ($e->getErrors() as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }

                return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
            }

            try {
                /** @var Listing $resource */
                $resource = $this->_objectManager->get(Listing::class);
                $resource->save($model);

                $this->messageManager->addSuccessMessage(__('The listing has been saved.'));
                $this->dataPersistor->clear('mirakl_seller_listing');

                $this->refreshProducts($model, true);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the listing.'));
            }

            $this->dataPersistor->set('mirakl_seller_listing', $data);

            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param   array   $data
     * @return  array
     */
    private function flatData(array $data)
    {
        $result = [];
        foreach ($data as $fields) {
            if (is_array($fields)) {
                $result = array_merge($result, $fields);
            }
        }

        return $result;
    }
}