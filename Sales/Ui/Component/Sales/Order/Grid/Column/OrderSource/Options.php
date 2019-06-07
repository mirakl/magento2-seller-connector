<?php
namespace MiraklSeller\Sales\Ui\Component\Sales\Order\Grid\Column\OrderSource;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Sales\Helper\Loader\Connection as ConnectionLoader;

class Options implements OptionSourceInterface
{
    /**
     * @var ConnectionLoader
     */
    protected $connectionLoader;

    /**
     * @param ConnectionLoader $connectionLoader
     */
    public function __construct(ConnectionLoader $connectionLoader)
    {
        $this->connectionLoader = $connectionLoader;
    }

    /**
     * @return  \MiraklSeller\Api\Model\ResourceModel\Connection\Collection
     */
    protected function getConnections()
    {
        return $this->connectionLoader->getConnections();
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [[
            'value' => '0',
            'label' => (string) __('Magento'),
        ]];

        /** @var Connection $connection */
        foreach ($this->getConnections() as $connection) {
            $options[] = [
                'value' => $connection->getId(),
                'label' => $connection->getName(),
            ];
        }

        return $options;
    }
}