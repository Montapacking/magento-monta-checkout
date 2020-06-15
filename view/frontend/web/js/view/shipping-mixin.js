/*global alert*/
define(
    [
    'jquery',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
    ], function (
        $,
        quote,
        $t
    ) {
        'use strict';

        return function (Component) {
            return Component.extend(
                {
                    validateShippingInformation: function () {
                        var originalResult = this._super();

                        if (quote.shippingMethod().carrier_code !== 'montapacking') {
                            return originalResult;
                        }

                        var checkoutConfig = window.checkoutConfig;
                        // Returns undefined if no option is checked.

                        var checkedOptionDelivery = $('input.montapacking_delivery_option:checked').val();
                        var checkedOptionPickup = $('input.initialPickupRadio:checked').val();

                        //console.log(checkoutConfig.quoteData.montapacking);
                        //console.log(checkedOptionDelivery);
                        //console.log(checkedOptionPickup);

                        if ((checkedOptionDelivery === undefined && checkedOptionPickup === undefined) || checkoutConfig.quoteData.montapacking === undefined) {
                            this.errorValidationMessage(
                                $t('Please select a delivery option. If no options are visible, please make sure you\'ve entered your address information correctly.')
                            );

                            return false;
                        }

                        var shippingAddress = quote.shippingAddress();

                        if (shippingAddress.extension_attributes === undefined) {
                            shippingAddress.extension_attributes = {};
                        }

                        shippingAddress.extension_attributes.montapacking = checkoutConfig.quoteData.montapacking;

                        return originalResult;
                    }
                }
            );
        };
    }
);
