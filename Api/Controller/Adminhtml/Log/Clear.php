<?php
namespace MiraklSeller\Api\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use MiraklSeller\Api\Model\Log\LoggerManager;

class Clear extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MiraklSeller_Api::config_developer';

    /**
     * @var LoggerManager
     */
    protected $loggerManager;

    /**
     * @param   Action\Context  $context
     * @param   LoggerManager   $loggerManager
     */
    public function __construct(
        Action\Context $context,
        LoggerManager $loggerManager
    ) {
        parent::__construct($context);
        $this->loggerManager = $loggerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('adminhtml/system_config/edit/section/mirakl_seller_api_developer'));

        $this->loggerManager->clear();
        $this->messageManager->addSuccessMessage(__('Log file has been cleared.'));

        return $resultRedirect;
    }
}
