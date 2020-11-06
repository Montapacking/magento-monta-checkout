var url = new URL(window.location.href).toString();

var urlPrefix = '';


if (url.includes('/nl/')) {
    urlPrefix = '/nl';
}

if (url.includes('/be/')) {
    urlPrefix = '/be';
}

if (url.includes('/de/')) {
    urlPrefix = '/de';
}

if (url.includes('/en/')) {
    urlPrefix = '/en';
}

if (url.includes('/fr/')) {
    urlPrefix = '/fr';
}

if (url.includes('/it/')) {
    urlPrefix = '/it';
}

if (url.includes('/es/')) {
    urlPrefix = '/es';
}

var loadUrl = urlPrefix + '/montacheckout/deliveryoptions/longlat';

function AJAX(url, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, false);
    xhr.send(null);
    if (xhr.status === 200) {
        cb(xhr.responseText)
    }
}

var googlekey = '';

AJAX(loadUrl, function(data) {
    var obj = JSON.parse(data);
    //console.log(obj.googleapikey)
    googlekey = obj.googleapikey;


})

var config = {
    waitSeconds: 20,
    paths: {
        'Handlebars': 'Montapacking_MontaCheckout/js/view/checkout/shipping/handlebars.min',
        'google': 'https://maps.google.com/maps/api/js?key='+googlekey,
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
            "deps": ["Handlebars", "jquery", "google"],
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




