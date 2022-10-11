<?php
declare(strict_types=1);

namespace MiraklSeller\Api\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class MiraklApi extends TagScope
{
    const TYPE_IDENTIFIER = 'mirakl_api';
    const CACHE_TAG = 'MIRAKL_API';
    const CACHE_LIFETIME = 86400;

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}