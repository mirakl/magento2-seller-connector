<?php
namespace MiraklSeller\Api\Helper\Client;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\MMP\Common\Domain\Collection\Locale\LocaleCollection;
use Mirakl\MMP\Common\Request\Locale\GetLocalesRequest;
use Mirakl\MMP\Shop\Client\ShopApiClient;
use MiraklSeller\Api\Model\Connection;

/**
 * @method ShopApiClient getClient(Connection $connection)
 */
class MMP extends AbstractClient
{
    const AREA_NAME = 'MMP';

    /**
     * @var array
     */
    protected $activeLocales;

    /**
     * (L01) Get active locales in Mirakl platform
     *
     * @param   Connection  $connection
     * @return  LocaleCollection
     */
    public function getActiveLocales(Connection $connection)
    {
        if (null === $this->activeLocales) {
            $this->activeLocales = $this->send($connection, new GetLocalesRequest());
        }

        return $this->activeLocales;
    }

    /**
     * {@inheritdoc}
     */
    protected function getArea()
    {
        return self::AREA_NAME;
    }

    /**
     * Returns Mirakl environment version of the specified connection
     *
     * @param   Connection  $connection
     * @return  string
     * @throws  LocalizedException
     */
    public function getVersion(Connection $connection)
    {
        $client = $this->getClient($connection);
        if (!$client->getBaseUrl() || !$client->getApiKey()) {
            throw new LocalizedException(__('Please specify your Mirakl API parameters.'));
        }

        return $client->getVersion()->getVersion();
    }

    /**
     * Verify that specified locale exists in Mirakl and if not reset it
     *
     * @param   Connection  $connection
     * @param   string      $locale
     * @return  null|string
     */
    protected function validateLocale(Connection $connection, $locale)
    {
        try {
            $locales = $this->getActiveLocales($connection)->walk('getCode');
        } catch (\Exception $e) {
            $this->_logger->critical($e);

            return null;
        }

        return in_array($locale, $locales) ? $locale : null;
    }
}
