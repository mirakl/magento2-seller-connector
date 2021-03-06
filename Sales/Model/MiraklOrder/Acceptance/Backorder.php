<?php
namespace MiraklSeller\Sales\Model\MiraklOrder\Acceptance;

use Magento\Framework\Data\OptionSourceInterface;
use MiraklSeller\Sales\Helper\Config;

class Backorder implements OptionSourceInterface
{
    const ACCEPT_ITEM_AUTOMATICALLY  = 1;
    const MANAGE_ORDER_MANUALLY      = 2;
    const REJECT_ITEM_AUTOMATICALLY  = 3;

    /**
     * @var array
     */
    protected static $options = [
        self::ACCEPT_ITEM_AUTOMATICALLY  => 'Accept item automatically',
        self::MANAGE_ORDER_MANUALLY      => 'Manage order manually',
        self::REJECT_ITEM_AUTOMATICALLY  => 'Reject item automatically',
    ];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Config  $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return  int
     */
    public function getConfig()
    {
        return $this->config->getBackorderBehavior();
    }

    /**
     * @return  array
     */
    public static function getOptions()
    {
        return static::$options;
    }

    /**
     * @return  bool
     */
    public function isAcceptItemAutomatically()
    {
        return $this->getConfig() === self::ACCEPT_ITEM_AUTOMATICALLY;
    }

    /**
     * @return  bool
     */
    public function isManageOrderManually()
    {
        return $this->getConfig() === self::MANAGE_ORDER_MANUALLY;
    }

    /**
     * @return  bool
     */
    public function isRejectItemAutomatically()
    {
        return $this->getConfig() === self::REJECT_ITEM_AUTOMATICALLY;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (static::getOptions() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => __($label),
            ];
        }

        return $options;
    }
}