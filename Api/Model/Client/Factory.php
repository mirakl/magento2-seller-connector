<?php
namespace MiraklSeller\Api\Model\Client;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use MiraklSeller\Api\Helper\Config as ApiConfig;
use MiraklSeller\Api\Helper\Data as ApiHelper;
use Mirakl\Core\Client\AbstractApiClient;
use Mirakl\MCI\Shop\Client\ShopApiClient as MCIShopApiClient;
use Mirakl\MMP\Shop\Client\ShopApiClient as MMPShopApiClient;

class Factory
{
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @param   ProductMetadataInterface    $productMetadata
     * @param   EventManagerInterface       $eventManager
     * @param   ApiConfig                   $apiConfig
     * @param   ApiHelper                   $apiHelper
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        EventManagerInterface $eventManager,
        ApiConfig $apiConfig,
        ApiHelper $apiHelper
    ) {
        $this->productMetadata = $productMetadata;
        $this->eventManager    = $eventManager;
        $this->apiConfig       = $apiConfig;
        $this->apiHelper       = $apiHelper;
    }

    /**
     * @param   string  $apiUrl
     * @param   string  $apiKey
     * @param   string  $area
     * @param   int     $shopId
     * @param   int     $timeout
     * @return  AbstractApiClient
     */
    public function create($apiUrl, $apiKey, $area, $shopId = null, $timeout = null)
    {
        switch ($area) {
            case 'MMP':
                $instanceName = MMPShopApiClient::class;
                break;
            case 'MCI':
                $instanceName = MCIShopApiClient::class;
                break;
            default:
                throw new \InvalidArgumentException('Could not create API client for area ' . $area);
        }

        /** @var AbstractApiClient $client */
        $client = new $instanceName($apiUrl, $apiKey, $shopId);
        $this->init($client);

        if ($timeout !== null) {
            // Add a connection timeout
            $client->addOption('connect_timeout', $timeout);
        }

        return $client;
    }

    /**
     * @param AbstractApiClient $client
     */
    private function init(AbstractApiClient $client)
    {
        // Customize User-Agent
        $userAgent = sprintf(
            'Magento-%s/%s Mirakl-Seller-Connector/%s %s',
            $this->productMetadata->getEdition(),
            $this->productMetadata->getVersion(),
            $this->apiHelper->getVersion(),
            AbstractApiClient::getDefaultUserAgent()
        );
        $client->setUserAgent($userAgent);

        $this->eventManager->dispatch('mirakl_seller_api_init_client', ['client' => $client]);

        // Disable API calls if needed
        if (!$this->apiConfig->isEnabled()) {
            $client->disable();
        }
    }
}
