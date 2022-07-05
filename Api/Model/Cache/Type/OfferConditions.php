<?php
declare(strict_types=1);

namespace MiraklSeller\Api\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class OfferConditions extends TagScope
{
    const TYPE_IDENTIFIER = 'mirakl_offer_conditions_api';
    const CACHE_TAG = 'MIRAKL_OFFER_CONDITIONS_API';
    const CACHE_LIFETIME = 86400; // 24 hours as recommended by Mirakl

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