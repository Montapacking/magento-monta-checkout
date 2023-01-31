define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/step-navigator',
        'Montapacking_MontaCheckout/js/view/checkout/shipping-information/pickup-shop'
    ], function (
        quote,
        stepNavigator,
        pickupShop
    ) {
        'use strict';

        var mixin = {

            isVisible: function () {

                if (pickupShop().parcelShopAddress() !== null) {
                    return false;
                }
                return !quote.isVirtual() && stepNavigator.isProcessed('shipping');

            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
