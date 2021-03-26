<?php
namespace MiraklSeller\Api\Ui\Component\Connection\Column;

use GuzzleHttp\Exception\RequestException;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Registry;
use Mirakl\MMP\Common\Domain\Shipping\Carrier;
use MiraklSeller\Api\Helper\Shipping;
use MiraklSeller\Api\Model\Connection;

class MiraklCarriers implements OptionSourceInterface
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Shipping
     */
    protected $shippingHelper;

    /**
     * @var bool
     */
    protected $emptyValue = true;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param   Registry    $coreRegistry
     * @param   Shipping    $shippingHelper
     */
    public function __construct(Registry $coreRegistry, Shipping $shippingHelper)
    {
        $this->coreRegistry = $coreRegistry;
        $this->shippingHelper = $shippingHelper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        if ($this->emptyValue) {
            $this->options = [
                [
                    'value' => '',
                    'label' => __('-- Please Select --'),
                ]
            ];
        } else {
            $this->options = [];
        }

        /** @var Connection $connection */
        $connection = $this->coreRegistry->registry('mirakl_seller_connection');

        if (!$connection->getApiKey() || !$connection->getApiUrl()) {
            return [['value' => '', 'label' => __('You need to save the connection before configuring the mapping')]];
        }

        try {
            $miraklCarriers = $this->shippingHelper->getCarriers($connection);
        } catch (RequestException $e) {
            return [['value' => '', 'label' => __('Mirakl cannot be reached')]];
        }

        foreach ($miraklCarriers as $carrier) {
            /** @var Carrier $carrier */
            $this->options[] = [
                'value' => $carrier->getCode(),
                'label' => $carrier->getLabel(),
            ];
        }

        return $this->options;
    }
}
