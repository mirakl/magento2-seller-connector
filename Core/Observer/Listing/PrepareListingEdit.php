<?php
namespace MiraklSeller\Core\Observer\Listing;

use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use MiraklSeller\Core\Controller\Adminhtml\RawMessagesTrait;
use MiraklSeller\Core\Helper\Connection as ConnectionHelper;
use MiraklSeller\Core\Model\Listing;
use Psr\Log\LoggerInterface;

class PrepareListingEdit implements ObserverInterface
{
    use RawMessagesTrait;

    /**
     * @var ConnectionHelper
     */
    private $connectionHelper;

    /**
     * @var State
     */
    private $state;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   ConnectionHelper    $connectionHelper
     * @param   State               $state
     * @param   ManagerInterface    $messageManager
     * @param   LoggerInterface     $logger
     */
    public function __construct(
        ConnectionHelper $connectionHelper,
        State $state,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->connectionHelper = $connectionHelper;
        $this->state = $state;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var Listing $listing */
        $listing = $observer->getData('listing');
        if (!$listing->getConnectionId()) {
            return;
        }

        try {
            $connection = $listing->getConnection();
            $this->connectionHelper->updateOfferAdditionalFields($connection);
        } catch (\Exception $e) {
            $this->logger->error($e);
            if ($this->state->getAreaCode() == 'adminhtml') {
                $message = __('Could not update offer additional fields: %1', $e->getMessage());
                $this->addRawErrorMessage($message);
            }
        }
    }
}