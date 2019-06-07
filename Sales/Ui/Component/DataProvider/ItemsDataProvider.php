<?php
namespace MiraklSeller\Sales\Ui\Component\DataProvider;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Model\Collection;
use MiraklSeller\Sales\Model\CollectionFactory;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;

class ItemsDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @var MiraklOrderLoader
     */
    protected $miraklOrderLoader;

    /**
     * @param   string                      $name
     * @param   string                      $primaryFieldName
     * @param   string                      $requestFieldName
     * @param   CollectionFactory           $collectionFactory
     * @param   ProductRepositoryInterface  $productRepository
     * @param   OrderHelper                 $orderHelper
     * @param   ConnectionLoader            $connectionLoader
     * @param   MiraklOrderLoader           $miraklOrderLoader
     * @param   array                       $meta
     * @param   array                       $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        OrderHelper $orderHelper,
        ConnectionLoader $connectionLoader,
        MiraklOrderLoader $miraklOrderLoader,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection        = $collectionFactory->create();
        $this->productRepository = $productRepository;
        $this->orderHelper       = $orderHelper;
        $this->connectionLoader  = $connectionLoader;
        $this->miraklOrderLoader = $miraklOrderLoader;

        $this->prepareUpdateUrl();
    }

    /**
     * @return  bool
     */
    public function canMassAcceptOrderLines()
    {
        if ($this->isOrderWaitingAcceptance()) {
            $this->loadOrderItems();
            /** @var DataObject $item */
            foreach ($this->collection as $item) {
                if ($item->getData('product_id')) {
                    return true; // If one Magento product is found, we can accept at least one item
                }
            }
        }

        return false;
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  ShopOrder
     */
    public function getMiraklOrder()
    {
        return $this->miraklOrderLoader->getCurrentMiraklOrder($this->getConnection());
    }

    /**
     * @return  void
     */
    protected function prepareUpdateUrl()
    {
        $connection = $this->connectionLoader->getCurrentConnection();
        $miraklOrder = $this->miraklOrderLoader->getCurrentMiraklOrder($connection);

        $this->data['config']['update_url'] = sprintf(
            '%s%s/%s/%s/%s',
            $this->data['config']['update_url'],
            'connection_id', $connection->getId(),
            'order_id', $miraklOrder->getId()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->loadOrderItems();

        return parent::getData();
    }

    /**
     * @return  bool
     */
    protected function isOrderWaitingAcceptance()
    {
        return $this->getMiraklOrder()->getStatus()->getState() === OrderState::WAITING_ACCEPTANCE;
    }

    /**
     * @return  $this
     */
    protected function loadOrderItems()
    {
        if ($this->collection->isLoaded()) {
            return $this;
        }

        if (!$connection = $this->connectionLoader->getCurrentConnection()) {
            return $this;
        }

        if (!$miraklOrder = $this->miraklOrderLoader->getCurrentMiraklOrder($connection)) {
            return $this;
        }

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $data                      = $orderLine->getData();
            $data['currency_iso_code'] = $miraklOrder->getCurrencyIsoCode();
            $data['offer_id']          = $orderLine->getOffer()->getId();
            $data['offer_sku']         = $orderLine->getOffer()->getSku();
            $data['product_name']      = $orderLine->getOffer()->getProduct()->getTitle();
            $data['shipping_title']    = $miraklOrder->getShipping()->getType()->getLabel();
            $data['status']            = $orderLine->getStatus()->getState();
            $data['unit_price']        = $orderLine->getOffer()->getPrice();
            $data['subtotal']          = $orderLine->getPrice();
            $data['tax']               = $this->orderHelper->getMiraklOrderLineTaxAmount($orderLine, true);
            $data['total_price']       = $data['subtotal'] + $data['shipping_price'] + $data['tax'];
            $data['product']           = null;
            $data['product_id']        = null;

            try {
                // Try to find attached product in Magento
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->productRepository->get($data['offer_sku']);
                $data['product']    = $product;
                $data['product_id'] = $product->getId();
            } catch (NoSuchEntityException $e) {
                // Ignore exception if product not found
            }

            $this->collection->addItem(new DataObject($data));
        }

        $this->collection->setIsLoaded();

        return $this;
    }
}