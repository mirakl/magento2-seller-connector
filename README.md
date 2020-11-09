# Mirakl Seller Connector for Magento 2
# Compatibility
Magento 2 Seller Connector is successfully tested on the following Magento versions:
- Magento Open Source 2.3 to 2.4
- Magento Commerce 2.3 to 2.4

# Installation
There is different ways to install the Mirakl connector for Magento 2.  
Please use one of the following method according to your needs.

## Method 1: Installation with Composer
### Installing the connector
**We advise you to backup your Magento folder and your database before installing the connector.**

    composer require mirakl/magento2-seller-connector
    php bin/magento module:enable MiraklSeller_Api MiraklSeller_Core MiraklSeller_Process MiraklSeller_Sales
    php bin/magento setup:upgrade
    php bin/magento cache:clean

### Updating the connector

    composer update
    php bin/magento setup:upgrade
    php bin/magento cache:clean

### Removing the connector

    php bin/magento module:disable MiraklSeller_Api MiraklSeller_Core MiraklSeller_Process MiraklSeller_Sales
    php bin/magento setup:upgrade
    php bin/magento cache:clean
    composer remove mirakl/magento2-seller-connector

## Method 2: Installation with Magento 2 Extension Manager
### Purchasing the connector (free)
You must purchase the connector in the [Magento Marketplace](https://marketplace.magento.com/mirakl-connector-magento2-seller.html) and then use the extension manager to install it.

**Warning: You cannot download the connector and install it directly, it will not work since the connector is designed as a Magento 2 metapackage. More information about metapackages here:**
https://devdocs.magento.com/guides/v2.3/extension-dev-guide/package/package_module.html#package-metapackage

Please refer to the following documentation to learn more about the extension manager:
https://devdocs.magento.com/guides/v2.3/comp-mgr/extens-man/extensman-main-pg.html

*Note: Due to some Magento configuration, it may happens that this installation does not work.
We advise you then to install the connector using Composer as described above.*
