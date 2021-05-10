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

                        if (quote.shippingMethod().carrier_code != 'montapacking') {
                            return originalResult;
                        }

                        if ($("#hasconnection").val() == "n") {
                            return originalResult;
                        }

                        var checkoutConfig = window.checkoutConfig;

                        var checkedOptionDelivery = $('input.montapacking_delivery_option:checked').val();
                        var checkedOptionPickup = $('input.initialPickupRadio:checked').val();

                        var hasData = true;

                        if (checkedOptionDelivery === undefined && checkedOptionPickup === undefined)
                        {
                            hasData = false;
                        }

                        if (checkoutConfig.quoteData.montapacking_montacheckout_data === undefined || checkoutConfig.quoteData.montapacking_montacheckout_data == "" || checkoutConfig.quoteData.montapacking_montacheckout_data == null || checkoutConfig.quoteData.montapacking_montacheckout_data == "null")
                        {
                            hasData = false;
                        }

                        if (checkoutConfig.quoteData.montapacking_montacheckout_data)
                        {
                            const obj = JSON.parse(checkoutConfig.quoteData.montapacking_montacheckout_data);

                            if (obj.type != "delivery" && obj.type != "pickup") {
                                hasData = false;
                            }
                        } else {
                            hasData = false;
                        }

                        if (hasData == false) {
                            this.errorValidationMessage(
                                $t('Please select a delivery option. If no options are visible, please make sure you\'ve entered your address information correctly.')
                            );

                            return false;
                        }

                        var shippingAddress = quote.shippingAddress();

                        if (shippingAddress.extension_attributes === undefined) {
                            shippingAddress.extension_attributes = {};
                        }

                        shippingAddress.extension_attributes.montapacking_montacheckout_data = checkoutConfig.quoteData.montapacking_montacheckout_data;

                        return originalResult;
                    }
                }
            );
        };
    }
);
