# Magento 2 Currency Conversion Extended Module Install Guide

**Using Composer:**
1. Go to your Magento 2 root folder
1. Type the following command to install the plugin `composer require sozodesign/magento2-currencyconversionextended`
1. Enable the module `php -f bin/magento module:enable --clear-static-content Magento2_CurrencyConversionExtended`
1. Database updates `php -f bin/magento setup:upgrade` 
1. Configure the module in the Magento 2 Admin. Go to Stores -> Configuration -> General > Currency Setup

**Cloning from GitHub**
1. Go to your Magento 2 root folder
1. Type `git clone git@github.com:clivewalkden/Magento2_CurrencyConversionExtended.git vendor/sozodesign/magento2-currencyconversionextended`
1. Enable the module `php -f bin/magento module:enable --clear-static-content Magento2_CurrencyConversionExtended`
1. Database updates `php -f bin/magento setup:upgrade` 
1. Configure the module in the Magento 2 Admin. Go to Stores -> Configuration -> General > Currency Setup