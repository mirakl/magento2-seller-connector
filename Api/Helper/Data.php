<?php
namespace MiraklSeller\Api\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Formats given size (in bytes) into an easy readable size
     *
     * @param   int     $size
     * @param   string  $separator
     * @return  string
     */
    public function formatSize($size, $separator = ' ')
    {
        $unit = ['bytes', 'kb', 'mb', 'gb', 'tb', 'pb'];
        $size = round($size / pow(1024, ($k = intval(floor(log($size, 1024))))), 2) . $separator . $unit[$k];

        return $size;
    }

    /**
     * Returns base media URL for specified store
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getBaseMediaUrl($store = null)
    {
        return $this->getStore($store)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Returns base URL for specified store
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getBaseUrl($store = null)
    {
        return $this->getStore($store)->getBaseUrl(UrlInterface::URL_TYPE_DIRECT_LINK);
    }

    /**
     * @param   mixed   $store
     * @return  Store|StoreInterface
     */
    public function getStore($store = null)
    {
        return $this->storeManager->getStore($store);
    }

    /**
     * Returns current version of the Magento Seller Connector
     *
     * @return  string
     */
    public function getVersion()
    {
        $matches = [];

        $ds = DIRECTORY_SEPARATOR;
        $file = MIRAKL_SELLER_BP . $ds . 'composer.json';
        if (file_exists($file)) {
            preg_match('#"version":\s+"(\d+\.\d+\.\d+-?.*)"#', file_get_contents($file), $matches);
        } else {
            $file = BP . $ds . 'vendor' . $ds . 'composer' . $ds . 'installed.json';
            if (file_exists($file)) {
                preg_match(
                    '#"mirakl/connector-magento2-seller",\n\s+"version":\s+"(\d+\.\d+\.\d+-?.*)"#',
                    file_get_contents($file),
                    $matches
                );
            }
        }

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Returns current version of the PHP SDK used by the Magento Seller Connector
     *
     * @return  string
     */
    public function getVersionSDK()
    {
        $matches = [];
        $packages = ['sdk-php-shop', 'sdk-php']; // try different package names
        foreach ($packages as $package) {
            $file = implode(DIRECTORY_SEPARATOR, [BP, 'vendor', 'mirakl', $package, 'composer.json']);
            if (file_exists($file)) {
                preg_match('#"version":\s+"(\d+\.\d+\.\d+-?.*)"#', file_get_contents($file), $matches);
            }
        }

        return isset($matches[1]) ? $matches[1] : '';
    }
}