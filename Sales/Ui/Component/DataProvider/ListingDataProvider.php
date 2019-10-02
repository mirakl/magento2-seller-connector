<?php
namespace MiraklSeller\Sales\Ui\Component\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NotFoundException;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Model\Collection;
use MiraklSeller\Sales\Model\CollectionFactory;
use MiraklSeller\Sales\Helper\Order as OrderHelper;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;
use MiraklSeller\Sales\Helper\Loader\MiraklOrder as MiraklOrderLoader;

class ListingDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

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
     * @var Filter[]
     */
    protected $filters = [];

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $limit = 20;

    /**
     * @var array
     */
    protected $filtersMap = [
        'id'     => 'order_ids',
        'status' => 'order_states',
    ];

    /**
     * @param   string              $name
     * @param   string              $primaryFieldName
     * @param   string              $requestFieldName
     * @param   CollectionFactory   $collectionFactory
     * @param   OrderHelper         $orderHelper
     * @param   ConnectionLoader    $connectionLoader
     * @param   MiraklOrderLoader   $miraklOrderLoader
     * @param   array               $meta
     * @param   array               $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper,
        ConnectionLoader $connectionLoader,
        MiraklOrderLoader $miraklOrderLoader,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection        = $collectionFactory->create();
        $this->orderHelper       = $orderHelper;
        $this->connectionLoader  = $connectionLoader;
        $this->miraklOrderLoader = $miraklOrderLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[$filter->getField()] = $filter;
    }

    /**
     * @return  array
     */
    protected function buildFilterParams()
    {
        $params = [];
        foreach ($this->filters as $filter) {
            $field = $filter->getField();
            if (isset($this->filtersMap[$field])) {
                $field = $this->filtersMap[$field];
            }

            $value = $filter->getValue();
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $params[$field] = $value;
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $this->loadOrders();

        return parent::getData();
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return $this->connectionLoader->getCurrentConnection();
    }

    /**
     * @return  $this
     */
    protected function loadOrders()
    {
        if ($this->collection->isLoaded()) {
            return $this;
        }

        try {
            $connection = $this->getConnection();
        } catch (NotFoundException $e) {
            return $this;
        }

        if (!$connection || !$connection->getId()) {
            return $this;
        }

        $params = $this->buildFilterParams();

        $miraklOrders = $this->miraklOrderLoader->getMiraklOrders($connection, $params, $this->offset, $this->limit);

        $this->collection->setTotalRecords($miraklOrders->getTotalCount());

        $magentoOrders = $this->orderHelper->getMagentoOrdersByMiraklOrderIds($miraklOrders->walk('getId'));

        /** @var \Mirakl\MMP\Shop\Domain\Order\ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            $data                   = $miraklOrder->getData();
            $data['status']         = $miraklOrder->getStatus() ? $miraklOrder->getStatus()->getState() : '';
            $data['shipping_price'] = $miraklOrder->getShipping()->getPrice(); // Excl. Tax
            $data['shipping_title'] = $miraklOrder->getShipping()->getType()->getLabel();
            $data['subtotal']       = $miraklOrder->getPrice(); // Excl. Tax

            // Add total tax amount
            $data['total_tax'] = $this->orderHelper->getMiraklOrderTaxAmount($miraklOrder, true);

            // Calculate grand total
            $data['grand_total'] = $miraklOrder->getTotalPrice() + $data['total_tax'];

            // Add Magento Order Id if found
            foreach ($magentoOrders as $magentoOrder) {
                /** @var \Magento\Sales\Model\Order $magentoOrder */
                if ($magentoOrder->getMiraklOrderId() == $miraklOrder->getId()) {
                    $data['magento_order_id'] = $magentoOrder->getId();
                    $data['magento_increment_id'] = $magentoOrder->getIncrementId();
                    break;
                }
            }

            $this->collection->addItem(new DataObject($data));
        }

        $this->collection->setIsLoaded();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit($offset, $size)
    {
        $this->offset = ($offset - 1) * $size;
        $this->limit = $size;
    }
}