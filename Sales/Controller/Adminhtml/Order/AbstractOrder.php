<?php
namespace MiraklSeller\Sales\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;

abstract class AbstractOrder extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Sales::orders';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var MiraklOrderLoader
     */
    protected $miraklOrderLoader;

    /**
     * @param   Context             $context
     * @param   PageFactory         $resultPageFactory
     * @param   ApiOrder            $apiOrder
     * @param   ConnectionLoader    $connectionLoader
     * @param   MiraklOrderLoader   $miraklOrderLoader
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ApiOrder $apiOrder,
        ConnectionLoader $connectionLoader,
        MiraklOrderLoader $miraklOrderLoader
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->apiOrder          = $apiOrder;
        $this->connectionLoader  = $connectionLoader;
        $this->miraklOrderLoader = $miraklOrderLoader;
    }

    /**
     * @return  Connection
     * @throws  NotFoundException
     */
    protected function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @param   Connection  $connection
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     */
    protected function getMiraklOrder(Connection $connection)
    {
        return $this->miraklOrderLoader->getCurrentMiraklOrder($connection);
    }

    /**
     * @param   Page    $resultPage
     * @return  Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Mirakl Orders'), __('Mirakl Orders'));

        return $resultPage;
    }

    /**
     * @param   string  $errorMessage
     * @param   bool    $referer
     * @return  \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
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
