var config = {
    waitSeconds: 20,
    paths: {
        'Handlebars': 'Montapacking_MontaCheckout/js/view/checkout/shipping/handlebars.min',
        'google': 'https://maps.google.com/maps/api/js?key=AIzaSyDuNPZlLCSlPM6Z55EUhanhzLZSiKcoUi0',
        'storeLocator': 'Montapacking_MontaCheckout/js/view/checkout/shipping/jquery.storelocator',

    },

    shim: {
        google: {
            exports: 'google'
        },

        Handlebars: {
            exports: 'Handlebars'
        },
        storeLocator: {
            "deps": ["Handlebars", "jquery"],
            "exports": "storeLocator"
        },

    },

    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Montapacking_MontaCheckout/js/view/shipping-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'Montapacking_MontaCheckout/js/view/shipping-information-mixin': true
            }
        }
    }
};




