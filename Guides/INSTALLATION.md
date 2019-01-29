# Magento 2 Currency Convertion Extended Module Install Guide

**Using Composer:**
1. Go to your Magento 2 root folder
1. Type the following command to install the plugin `composer require sozodesign/magento2-currencyconvertionextended`
1. Enable the module `php -f bin/magento module:enable --clear-static-content Magento2_CurrencyConvertionExtended`
1. Database updates `php -f bin/magento setup:upgrade` 
1. Configure the module in the Magento 2 Admin. Go to Stores -> Configuration -> General > Currency Setup

**Cloning from GitHub**
1. Go to your Magento 2 root folder
1. Type `git clone git@github.com:clivewalkden/Magento2_CurrencyConvertionExtended.git vendor/sozodesign/magento2-currencyconvertionextended`
1. Enable the module `php -f bin/magento module:enable --clear-static-content Magento2_CurrencyConvertionExtended`
1. Database updates `php -f bin/magento setup:upgrade` 
1. Configure the module in the Magento 2 Admin. Go to Stores -> Configuration -> General > Currency Setup