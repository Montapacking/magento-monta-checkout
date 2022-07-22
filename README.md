# Monta Checkout for Magento 2
Magento plugin to integrate the Monta shipping options


## Installation without using Composer
_Clone or download_ the contents of this repository into `app/code/Montapacking/MontaCheckout`.

### Development Mode
After installation, run `bin/magento setup:upgrade` to make the needed database changes and remove/empty Magento 2's generated files and folders.

### Production Mode
After installation, run:
1. `bin/magento setup:upgrade`
2. `bin/magento setup:di:compile`
3. `bin/magento setup:static-content:deploy [locale-codes, e.g. nl_NL en_US`
4. `bin/magento cache:flush`

## Configuration
The options can be found in Stores > Configuration > Sales > Shipping Methods > Monta.

### API credentials
To use this module you need API credentials provided by Monta ('Monta webshop', 'Monta webshop', 'Monta - Password').

## Google API
Specify a valid Google Maps API key here. A key can be created here: https://developers.google.com/maps/documentation/javascript/get-api-key.
This key is needed for the map with pick-up points.
 
## Shipping Costs
The base shipping costs used when there is a possible connection error with the Monta API.




