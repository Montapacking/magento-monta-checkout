define(
    [
        'uiComponent',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/sidebar',
        'Montapacking_MontaCheckout/js/view/checkout/shipping-information/pickup-shop'
        // @codingStandardsIgnoreLine
    ], function (
        uiComponent,
        $,
        ko,
        quote,
        stepNavigator,
        sidebar,
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
