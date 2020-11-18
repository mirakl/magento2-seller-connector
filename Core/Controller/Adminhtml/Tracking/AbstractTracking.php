<?php
namespace MiraklSeller\Core\Controller\Adminhtml\Tracking;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use MiraklSeller\Core\Model\Listing;
use MiraklSeller\Core\Model\Listing\Tracking\Offer as OfferTracking;
use MiraklSeller\Core\Model\Listing\Tracking\Product as ProductTracking;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer as OfferResource;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Offer\Collection as OfferCollection;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product as ProductResource;
use MiraklSeller\Core\Model\ResourceModel\Listing\Tracking\Product\Collection as ProductCollection;

abstract class AbstractTracking extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_core::listing';

    /**
     * @param   bool    $mustExists
     * @return  ProductTracking|OfferTracking
     * @throws  NotFoundException
     */
    protected function getTracking($mustExists = false)
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var ProductTracking|OfferTracking $tracking */
        $tracking = $this->getTrackingModel();
        $this->getTrackingResource()->load($tracking, $id);

        if ($mustExists && !$tracking->getId()) {
            $message = __('This tracking no longer exists.');
            $this->messageManager->addErrorMessage($message);
            throw new NotFoundException($message);
        }

        return $tracking;
    }

    /**
     * @return  ProductCollection|OfferCollection
     */
    protected function getTrackingCollection()
    {
        $type = $this->getTrackingType();
        if ($type == Listing::TYPE_PRODUCT) {
            return $this->_objectManager->create(ProductCollection::class);
        }

        return $this->_objectManager->create(OfferCollection::class);
    }

    /**
     * @return  ProductTracking|OfferTracking
     */
    protected function getTrackingModel()
    {
        $type = $this->getTrackingType();
        if ($type == Listing::TYPE_PRODUCT) {
            return $this->_objectManager->create(ProductTracking::class);
        }

        return $this->_objectManager->create(OfferTracking::class);
    }

    /**
     * @return  ProductResource|OfferResource
     */
    protected function getTrackingResource()
    {
        $type = $this->getTrackingType();
        if ($type == Listing::TYPE_PRODUCT) {
            return $this->_objectManager->create(ProductResource::class);
        }

        return $this->_objectManager->create(OfferResource::class);
    }

    /**
     * @return  string
     * @throws  LocalizedException
     */
    protected function getTrackingType()
    {
        $type = strtoupper($this->getRequest()->getParam('type'));
        if (!in_array($type, [Listing::TYPE_PRODUCT, Listing::TYPE_OFFER])) {
            throw new LocalizedException(__('Bad tracking type specified: %1', $type));
        }

        return $type;
    }

    /**
     * @param   string  $errorMessage
     * @param   bool    $referer
     * @return  \Magento\Framework\Controller\ResultInterface
     */
    protected function redirectError($errorMessage, $referer = false)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($referer) {
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
