<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldCollection;
use Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest;
use MiraklSeller\Api\Model\Cache\Type\MiraklApi;
use MiraklSeller\Api\Model\Client\Manager;
use MiraklSeller\Api\Model\Connection;
use MiraklSeller\Api\Model\Log\LoggerManager;
use MiraklSeller\Api\Model\Log\RequestLogValidator;

class AdditionalField extends Client\MMP
{
    const ADDITIONAL_FIELDS_CACHE_PREFIX = 'additional_fields';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context             $context
     * @param Manager             $manager
     * @param LoggerManager       $loggerManager
     * @param RequestLogValidator $requestLogValidator
     * @param CacheInterface      $cache
     * @param SerializerInterface $serializer
     * @param EncryptorInterface  $encryptor
     */
    public function __construct(
        Context $context,
        Manager $manager,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator,
        CacheInterface $cache,
        SerializerInterface $serializer,
        EncryptorInterface $encryptor
    ) {
        parent::__construct(
            $context,
            $manager,
            $loggerManager,
            $requestLogValidator
        );
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->encryptor = $encryptor;
    }

    /**
     * (AF01) Get the list of any additional fields
     *
     * We use cache as Mirakl limits the number of allowed calls per day for this API
     *
     * @param   Connection  $connection
     * @param   array       $entities   For example: ['OFFER', 'SHOP']
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getAdditionalFields(Connection $connection, $entities, $locale = null)
    {
        $cacheKey = $this->encryptor->hash($this->serializer->serialize(array_merge($entities, [$locale])));
        $cacheId = MiraklApi::TYPE_IDENTIFIER . '_' . self::ADDITIONAL_FIELDS_CACHE_PREFIX . '_' . $connection->getId() . '_' . $cacheKey;
        $additionalFieldsData = $this->cache->load($cacheId);

        if ($additionalFieldsData === false) {
            $request = new GetAdditionalFieldRequest();
            $request->setEntities($entities);
            $request->setLocale($this->validateLocale($connection, $locale));
            $this->_eventManager->dispatch('mirakl_seller_api_additional_fields_before', [
                'request' => $request,
            ]);
            $additionalFields = $this->send($connection, $request);
            $this->cache->save(
                $this->serializer->serialize($additionalFields->toArray()),
                $cacheId,
                [MiraklApi::CACHE_TAG],
                MiraklApi::CACHE_LIFETIME
            );
        } else {
            $additionalFieldsData = $this->serializer->unserialize($additionalFieldsData);
            $additionalFields = new AdditionalFieldCollection($additionalFieldsData);
        }

        return $additionalFields;
    }

    /**
     * @param   Connection  $connection
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getOfferAdditionalFields(Connection $connection, $locale = null)
    {
        return $this->getAdditionalFields($connection, ['OFFER'], $locale);
    }
}
