<?php
namespace MiraklSeller\Sales\Helper\Order\Acceptance;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Helper\Order as ApiOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\ConnectionFactory;
use MiraklSeller\Api\Model\ResourceModel\ConnectionFactory as ConnectionResourceFactory;
use MiraklSeller\Core\Helper\Config;
use MiraklSeller\Process\Model\Process as ProcessModel;
use MiraklSeller\Sales\Helper\Order\Price as PriceHelper;
use MiraklSeller\Sales\Model\MiraklOrder\Acceptance\Backorder;
use MiraklSeller\Sales\Model\MiraklOrder\Acceptance\InsufficientStock;
use MiraklSeller\Sales\Model\MiraklOrder\Acceptance\PricesVariations;

class Process extends AbstractHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var ApiOrder
     */
    protected $apiOrder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var ConnectionResourceFactory
     */
    protected $connectionResourceFactory;

    /**
     * @var Backorder
     */
    protected $backorderHandler;

    /**
     * @var InsufficientStock
     */
    protected $insufficientStockHandler;

    /**
     * @var PricesVariations
     */
    protected $pricesVariationsHandler;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @param   Context                     $context
     * @param   ProductRepositoryInterface  $productRepository
     * @param   StockRegistryInterface      $stockRegistry
     * @param   ApiOrder                    $apiOrder
     * @param   Config                      $config
     * @param   ConnectionFactory           $connectionFactory
     * @param   ConnectionResourceFactory   $connectionResourceFactory
     * @param   Backorder                   $backorderHandler
     * @param   InsufficientStock           $insufficientStockHandler
     * @param   PricesVariations            $pricesVariationsHandler
     * @param   PriceHelper                 $priceHelper
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        ApiOrder $apiOrder,
        Config $config,
        ConnectionFactory $connectionFactory,
        ConnectionResourceFactory $connectionResourceFactory,
        Backorder $backorderHandler,
        InsufficientStock $insufficientStockHandler,
        PricesVariations $pricesVariationsHandler,
        PriceHelper $priceHelper
    ) {
        parent::__construct($context);

        $this->productRepository         = $productRepository;
        $this->stockRegistry             = $stockRegistry;
        $this->apiOrder                  = $apiOrder;
        $this->config                    = $config;
        $this->connectionFactory         = $connectionFactory;
        $this->connectionResourceFactory = $connectionResourceFactory;
        $this->backorderHandler          = $backorderHandler;
        $this->insufficientStockHandler  = $insufficientStockHandler;
        $this->pricesVariationsHandler   = $pricesVariationsHandler;
        $this->priceHelper               = $priceHelper;
    }

    /**
     * @param   ProcessModel    $process
     * @param   int             $connectionId
     * @return  ProcessModel
     */
    public function acceptConnectionOrders(ProcessModel $process, $connectionId)
    {
        $connection = $this->getConnectionById($connectionId);

        if (!$connection->getId()) {
            return $process->fail(__("Could not find connection with id '%1'", $connectionId));
        }

        $process->output(__("Accepting Mirakl orders of connection '%1' (id: %2) ...", $connection->getName(), $connection->getId()));

        $params = ['order_states' => [\Mirakl\MMP\Common\Domain\Order\OrderState::WAITING_ACCEPTANCE]];
        $miraklOrders = $this->apiOrder->getAllOrders($connection, $params);

        if (!$miraklOrders->count()) {
            return $process->output(__('No Mirakl order to accept for this connection'));
        }

        /** @var ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            try {
                $process->output(__('Processing Mirakl order #%1 ...', $miraklOrder->getId()));
                $this->acceptMiraklOrder($process, $connection, $miraklOrder);
            } catch (\Exception $e) {
                $process->output(__('ERROR: %1', $e->getMessage()));
            }
        }

        return $process;
    }

    /**
     * @param   ProcessModel    $process
     * @param   Connection      $connection
     * @param   ShopOrder       $miraklOrder
     * @return  ProcessModel
     */
    public function acceptMiraklOrder(ProcessModel $process, Connection $connection, ShopOrder $miraklOrder)
    {
        // Build order lines to accept
        $orderLines = [];

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $accepted = true; // Order line is accepted by default

            $offerSku = $orderLine->getOffer()->getSku();

            try {
                /** @var Product $product */
                $product = $this->productRepository->get($offerSku);

                $magentoPrice = $this->priceHelper->getMagentoPrice($product, $connection, $orderLine->getQuantity());

                // Handle allowed prices variations on product
                $miraklPrice = $orderLine->getOffer()->getPrice();
                if (!$this->pricesVariationsHandler->isPriceVariationValid((float) $magentoPrice, (float) $miraklPrice)) {
                    return $process->output(__('Product with SKU "%1" has an invalid price variation. Please handle order manually.', $offerSku));
                }

                // Try to load associated stock item
                $stockItem = $this->stockRegistry->getStockItem($product->getId());

                if (!$stockItem->getIsInStock()) {
                    // Case we have out of stock flag on product
                    if ($this->insufficientStockHandler->isManageOrderManually()) {
                        return $process->output(__('Product with SKU "%1" is out of stock. Please handle order manually.', $offerSku));
                    }

                    $process->output(__('Product with SKU "%1" is out of stock. Product refused.', $offerSku));
                    $accepted = false; // Insufficient stock config is "auto reject item"
                } elseif ($stockItem->getQty() < $orderLine->getQuantity()) {
                    // Case we have stock item qty under order line qty
                    if (!$stockItem->getBackorders()) {
                        // Case we have backorders disabled on stock item and not enough stock
                        if ($this->insufficientStockHandler->isManageOrderManually()) {
                            return $process->output(__('Product with SKU "%1" has not enough stock. Please handle order manually.', $offerSku));
                        }

                        $process->output(__('Product with SKU "%1" has not enough stock. Product refused.', $offerSku));
                        $accepted = false; // Insufficient stock config is "auto reject item"
                    } else {
                        // Case we have backorders allowed on stock item
                        if ($this->backorderHandler->isManageOrderManually()) {
                            return $process->output(__('Product with SKU "%1" has backorders enabled. Please handle order manually.', $offerSku));
                        } elseif ($this->backorderHandler->isRejectItemAutomatically()) {
                            $process->output(__('Product with SKU "%1" has backorders enabled. Product refused.', $offerSku));
                            $accepted = false;
                        } else {
                            $process->output(__('Product with SKU "%1" is accepted.', $offerSku));
                        }
                    }
                }
            } catch (NoSuchEntityException $e) {
                // Case we cannot find associated product
                return $process->output(__('Product with SKU "%1" was not found. Please handle order manually.', $offerSku));
            }

            $orderLines[] = [
                'id'       => $orderLine->getId(),
                'accepted' => $accepted,
            ];
        }

        $this->apiOrder->acceptOrder($connection, $miraklOrder->getId(), $orderLines);

        $process->output(__('Order has been accepted successfully.'));

        return $process;
    }

    /**
     * Retrieves Mirakl connection by specified id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function getConnectionById($connectionId)
    {
        $connection = $this->connectionFactory->create();
        $this->connectionResourceFactory->create()->load($connection, $connectionId);

        return $connection;
    }
}