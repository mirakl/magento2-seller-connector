<?php
declare(strict_types=1);

namespace MiraklSeller\Api\Setup\Patch\Data;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MiraklSeller\Api\Model\Cache\Type\MiraklApi;

class EnableMiraklApiCache implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var StateInterface
     */
    private $cacheState;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param StateInterface           $cacheState
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        StateInterface $cacheState
    ) {
        $this->setup = $setup;
        $this->cacheState = $cacheState;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->setup->startSetup();

        $this->cacheState->setEnabled(MiraklApi::TYPE_IDENTIFIER, true);

        $this->setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}