define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/quote',
        'Montapacking_MontaCheckout/js/helper/address-finder',
        'Montapacking_MontaCheckout/js/view/checkout/shipping-information/pickup-shop',
        'Handlebars',
        'google',
        'storeLocator'
    ], function (
        $,
        Component,
        ko,
        priceUtils,
        quote,
        AddressFinder,
        pickupShop,
        Handlebars,
        google,
        storeLocator
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Montapacking_MontaCheckout/checkout/shipping/additional-block',
                    postcode: null,
                    country: null,
                    hasconnection: 'true',
                    deliveryServices: ko.observableArray([]),
                    pickupServices: ko.observableArray([]),
                    deliveryFee: ko.observable(),
                    pickupFee: ko.observable(),
                    selectedShippers: ko.observable(),
                    selectedPickup: ko.observable(),

                },

                initObservable: function () {

                    self = this;

                    this.selectedMethod = ko.computed(
                        function () {
                            var method = quote.shippingMethod();
                            var selectedMethod = method != null ? method.carrier_code + '_' + method.method_code : null;

                            return selectedMethod;
                        }, this
                    );


                    this.tabClasses = ko.computed(
                        function () {
                            return 'montapacking-tabs';
                        }, this
                    );


                    this._super().observe(
                        [
                            'postcode',
                            'hasconnection',
                            'country',
                            'street',
                            'deliveryServices',
                            'pickupPoints'
                        ]
                    );


                    AddressFinder.subscribe(
                        function (address) {

                            if (typeof address == "undefined") {
                                return;
                            }

                            var old_address = $("#old_address").val();

                            if (!address || JSON.stringify(address) == old_address) {
                                return;
                            }

                            $("#old_address").val(JSON.stringify(address));

                            // Reset frontend storage before triggering any new calls.
                            this.deliveryFee(null);
                            this.pickupFee(null);
                            this.hasconnection('true');

                            this.getLongLat(address.street, address.postcode, address.city, address.country);
                            this.getPickupServices(address.street, address.postcode, address.city, address.country);
                            this.getDeliveryServices(address.street, address.postcode, address.city, address.country);

                            this.loadDeliveryJs(true);


                        }.bind(this)
                    );

                    self.loadPopup();

                    return this;
                },

                loadDeliveryJs: function (timeout = false) {
                    if (true === timeout) {
                        setTimeout(
                            function () {
                                self.toggleTab('.montapacking-tab-pickup', '.montapacking-tab-delivery', '.pickup-services', '.delivery-services', true);
                            }, 3000
                        );
                    } else {
                        self.toggleTab('.montapacking-tab-pickup', '.montapacking-tab-delivery', '.pickup-services', '.delivery-services', true);
                    }

                },

                /**
                 * Retrieve LONG LAT
                 */
                getLongLat: function (street, postcode, city, country) {


                    $.ajax(
                        {
                            method: 'GET',
                            url: '/montacheckout/deliveryoptions/longlat',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country
                            }
                        }
                    ).done(
                        function (services) {

                            $("#montapacking_longitude").val(services.longitude);
                            $("#montapacking_latitude").val(services.latitude);
                            $("#montapacking_language").val(services.language);

                            if (services.hasconnection == 'false') {
                                this.hasconnection(null);
                            }

                        }.bind(this)
                    );
                },

                /**
                 * Retrieve Delivery Options from Montapacking.
                 */
                getDeliveryServices: function (street, postcode, city, country) {

                    $.ajax(
                        {
                            method: 'GET',
                            url: '/montacheckout/deliveryoptions/delivery',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country
                            }
                        }
                    ).done(
                        function (services) {
                            const objectArray = Object.values(services);
                            this.deliveryServices(objectArray);

                        }.bind(this)
                    );
                },

                /**
                 * Retrieve Delivery Options from Montapacking.
                 */
                getPickupServices: function (street, postcode, city, country) {

                    $.ajax(
                        {
                            method: 'GET',
                            url: '/montacheckout/deliveryoptions/pickup',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country
                            }
                        }
                    ).done(
                        function (services) {
                            const objectArray = Object.values(services);
                            this.pickupServices(objectArray);

                            var counter = 0;
                            $(".montapacking-pickup-service.pickup-option").each(
                                function (index) {
                                    counter++;
                                    $(this).addClass("overruleshow");

                                    if (counter == 3) {
                                        return false;
                                    }

                                }
                            );

                        }.bind(this)
                    );
                },

                setDeliveryOption: function (type, details, additional_info) {

                    var deliveryOption = {
                        type: type,
                        details: details,
                        additional_info: additional_info
                    };

                    var checkoutConfig = window.checkoutConfig;
                    // Do not refactor this.
                    checkoutConfig.quoteData.montapacking_montacheckout_data = JSON.stringify(deliveryOption);

                },

                toggleTab: function (previousTab, currentTab, previousContent, currentContent, triggerClick = false) {
                    $(previousTab).removeClass('active');
                    $(currentTab).addClass('active');
                    $(previousContent).hide();
                    $(currentContent).fadeIn('slow');

                    if (true === triggerClick) {
                        if (currentTab == '.montapacking-tab-pickup') {
                            $("input.selectshipment").val("pickup");
                            $(".pickup-option:first").find("input.initialPickupRadio").trigger("click");
                        } else {
                            $("input.selectshipment").val("delivery");
                            $(".delivery-option:not(.SameDayDelivery):first").find("input[class=montapacking_delivery_option]").trigger("click");

                            if ($(".SameDayDelivery").length) {
                                $(".havesameday").removeClass("displaynone");
                            } else {
                                $(".nothavesameday").removeClass("displaynone");
                            }
                        }
                    }

                },

                showDeliveryOptions: function (informationTab, optionsTab) {
                    $(informationTab).hide();
                    $(optionsTab).fadeIn('slow');
                },


                selectShipper: function () {

                    $(".delivery-information").hide();

                    // set vars

                    var code = $(this).val();
                    var name = $(this).parents(".delivery-option").find(".cropped_name").text();
                    var type = $(this).parents(".delivery-option").find(".cropped_type").text();
                    var date = $(this).parents(".delivery-option").find(".cropped_date").text();
                    var date_text = $(this).parents(".delivery-option").find(".cropped_time").text();
                    var date_string = $(this).parents(".delivery-option").find(".cropped_date_text").text();

                    if (date == '01-01-1970') {
                        date = '';
                        date_text = '';
                    }

                    var time = $(this).parents(".delivery-option").find(".cropped_time").text();
                    var time_text = $(this).parents(".delivery-option").find(".cropped_time_text").text();
                    var price = $(this).parents(".delivery-option").find(".cropped_price").text();
                    var image_class = $(this).parents(".delivery-option").find(".cropped_image_class").text();
                    var short_code = image_class;
                    var checked_boxes = $(this).parents(".delivery-option").find(".montapacking-container-delivery-options input[type=checkbox]:checked");
                    var option_codes = $(this).parents(".delivery-option").find(".montapacking-container-delivery-optioncodes input[type=hidden]");
                    var total_price = parseFloat(price);

                    // set delivery information
                    $(".delivery-information").find(".montapacking-delivery-information-company").html(name);
                    $(".delivery-information").find(".montapacking-delivery-information-date").html(date_string);


                    if (date == '') {
                        $(".dateblock").css("display", "none");
                    } else {
                        $(".dateblock").css("display", "block");
                    }

                    if (time == '00:00-00:00' || time == '') {
                        $(".timeblock").css("display", "none");
                    } else {
                        $(".timeblock").css("display", "block");
                    }
                    $(".delivery-information").find(".montapacking-delivery-information-time").html(time_text);

                    //set image class
                    $(".delivery-information").find(".montapacking-container-logo").removeClass().addClass("montapacking-container-logo").addClass(image_class);

                    if (type == 'ShippingDay') {
                        $(".delivery-information").find(".delivered").addClass("displaynone");
                        $(".delivery-information").find(".shipped").removeClass("displaynone");
                    } else {
                        $(".delivery-information").find(".delivered").removeClass("displaynone");
                        $(".delivery-information").find(".shipped").addClass("displaynone");
                    }

                    //set delivery options

                    $("ul.montapacking-delivery-information-options").empty();

                    var options = [];

                    $(checked_boxes).each(
                        function (index, element) {
                            var text_value = $(element).parent("div").find("label").html();
                            $("ul.montapacking-delivery-information-options").append('<li>' + text_value + '</li>');

                            var raw_price = $(element).parents(".montapacking-delivery-option").find(".delivery-fee-hidden").text();
                            var option_price = parseFloat(raw_price);
                            total_price += option_price;

                            options.push($(this).val());
                        }
                    );

                    $(option_codes).each(
                        function (index, element) {
                            options.push($(element).val());
                        }
                        //options.push();
                    );


                    $('.delivery-option input[type=checkbox]:checked').not(checked_boxes).attr('checked', false);

                    $(".delivery-information").fadeIn('slow');
                    $(".delivery-option").hide();

                    total_price = total_price.toFixed(2);
                    var total_price_raw = total_price;

                    total_price = total_price.toString().replace('.', ',');

                    setTimeout(
                        function () {
                            $(".table-checkout-shipping-method").find("input:checked").parents(".row").find("span.price").text("€ " + total_price);
                        }, 250
                    );


                    $(".delivery-information").find(".montapacking-container-price").html("&euro; " + total_price);

                    var additional_info = [];
                    additional_info.push(
                        {
                            code: code,
                            name: name,
                            date: date,
                            time: time,
                            price: price,
                            total_price: total_price_raw,
                        }
                    );

                    var details = [];
                    details.push(
                        {
                            short_code: short_code,
                            options: options,
                        }
                    );

                    self.setDeliveryOption('delivery', details, additional_info);
                    self.deliveryFee(total_price);

                    pickupShop().parcelShopAddress(null);

                    return true;

                },


                selectPickUp: function () {

                    $(".pickup-information").hide();

                    // set vars
                    var code = $(this).val();
                    var shipper = $(this).parents(".pickup-option").find(".cropped_shipper").text();
                    var code_pickup = $(this).parents(".pickup-option").find(".cropped_codepickup").text();
                    var shippingoptions = $(this).parents(".pickup-option").find(".cropped_shippingoptions").text();
                    var company = $(this).parents(".pickup-option").find(".cropped_company").text();
                    var street = $(this).parents(".pickup-option").find(".cropped_street").text();
                    var housenumber = $(this).parents(".pickup-option").find(".cropped_housenumber").text();
                    var postal = $(this).parents(".pickup-option").find(".cropped_postal").text();
                    var city = $(this).parents(".pickup-option").find(".cropped_city").text();
                    var description = $(this).parents(".pickup-option").find(".cropped_description").text();
                    var country = $(this).parents(".pickup-option").find(".cropped_country").text();
                    var price = $(this).parents(".pickup-option").find(".cropped_price").text();
                    var image_class = $(this).parents(".pickup-option").find(".cropped_image_class").text();
                    var short_code = image_class;
                    var distance = $(this).parents(".pickup-option").find(".cropped_distance").text();
                    var optionsvalues = $(this).parents(".pickup-option").find(".cropped_optionswithvalue").text();
                    var openingtimes_html = $(this).parents(".pickup-option").find(".table-container .table").clone().html();
                    var total_price = parseFloat(price);


                    // set pickup information
                    $(".pickup-information").find(".montapacking-pickup-information-company").html(company);
                    $(".pickup-information").find(".montapacking-pickup-information-description-distance").html(description);
                    $(".pickup-information").find(".montapacking-pickup-information-description-street-housenumber").html(street + ' ' + housenumber);
                    $(".pickup-information").find(".montapacking-pickup-information-description-postal-city-country").html(postal + ' ' + city + ' (' + country + ')');
                    $(".pickup-information").find(".table-container .table").html(openingtimes_html);

                    // set price
                    $(".pickup-information").find(".montapacking-container-price").html("&euro; " + price.replace(".", ","));

                    //set image class
                    $(".pickup-information").find(".montapacking-container-logo").removeClass().addClass("montapacking-container-logo").addClass(image_class);

                    $(".pickup-information").fadeIn('slow');

                    total_price = total_price.toFixed(2);
                    var total_price_raw = total_price;

                    total_price = total_price.toString().replace('.', ',');

                    setTimeout(
                        function () {
                            $(".table-checkout-shipping-method").find("input:checked").parents(".row").find("span.price").text("€ " + total_price);
                        }, 250
                    );

                    var additional_info = [];
                    additional_info.push(
                        {
                            code: code,
                            code_pickup: code_pickup,
                            shipper: shipper,
                            company: company,
                            street: street,
                            housenumber: housenumber,
                            postal: postal,
                            city: city,
                            description: description,
                            country: country,
                            price: price,
                            country: country,
                            total_price: total_price_raw,
                        }
                    );

                    var details = [];
                    details.push(
                        {
                            short_code: short_code,
                            options: [],
                        }
                    );

                    self.setDeliveryOption('pickup', details, additional_info);
                    self.pickupFee(total_price);
                    pickupShop().parcelShopAddress(additional_info[0]);

                    return true;

                },

                showBusinessHours: function () {
                    $(this).hide();
                    $(this).parents(".montapacking-pickup-service").find('.table-container').fadeIn('slow');
                },

                closeBusinessHours: function () {
                    $(this).parent('.table-container').hide();
                    $(this).parents(".montapacking-pickup-service").find('.open-business-hours').fadeIn('slow');
                },


                showPopup: function (sHtml) {
                    $("#modular-container").css("display", "table");
                    $("#modular-background").css("display", "block");
                    /*
                    $("body").prepend('<div id="modular-container"/>');
                    $("body").prepend('<div id="modular-background"/>');

                    $("#modular-container").append(
                        '<div class="positioning">' + sHtml +
                        '</div>'
                    );
                    */
                    //ko.applyBindings(self, document.getElementById('modular-container'))
                },

                loadPopup: function (sHtml) {

                    $("body").prepend('<div id="modular-container"/>');
                    $("body").prepend('<div id="modular-background"/>');

                    var html = "<div id=\"storelocator_container\">\n" +
                        "    <div class=\"container\">\n" +
                        "        <div class=\"bh-sl-container\">\n" +
                        "            <div class=\"bh-sl-filters-container\">\n" +
                        "                <button href=\"javascript:;\" data-bind=\"click: closePopup, i18n: 'Use selection'\" class=\"select-item displaynone\"></button>\n" +
                        "                <ul id=\"category-filters\" class=\"bh-sl-filters\"></ul>\n" +
                        "            </div>\n" +
                        "            <div id=\"bh-sl-map-container\" class=\"bh-sl-map-container\">\n" +
                        "                <div id=\"bh-sl-map\" class=\"bh-sl-map\"></div>\n" +
                        "                <div class=\"bh-sl-loc-list\">\n" +
                        "                    <ul class=\"list listitemsforpopup\"></ul>\n" +
                        "                </div>\n" +
                        "            </div>\n" +
                        "        </div>\n" +
                        "    </div>\n" +
                        "</div>";

                    $("#modular-container").append(
                        '<div class="positioning">' + html + '</div>'
                    );

                    ko.applyBindings(self, document.getElementById('modular-container'))
                },

                closePopup: function () {

                    //var useLocator = $('#bh-sl-map-container');
                    //useLocator.storeLocator('reset');

                    $("#modular-container").css("display", "none");
                    $("#modular-background").css("display", "none");
                    return false;

                },

                openStoreLocator: function () {

                    require(
                        ['Handlebars',
                            'jquery',
                            'google',
                            'storeLocator'], function (Handlebars, $, google, storeLocator) {

                            window.Handlebars = Handlebars;

                            var useLocator = $('#bh-sl-map-container');

                            //console.log(useLocator.html());

                            var site_url = '/static/frontend/Magento/luma/nl_NL/Montapacking_MontaCheckout';
                            /* Map */
                            if (useLocator) {

                                var markers = [];

                                $(".montapacking-pickup-service.pickup-option").each(
                                    function (index) {

                                        var openingtimes = $(this).find(".table-container .table").html();

                                        markers.push(
                                            {
                                                'id': $(this).attr("data-markerid"),
                                                'listid': $(this).attr("data-markerid"),
                                                'category': $(this).find("span.cropped_shipper").text(),
                                                'code': $(this).find("span.cropped_code").text(),
                                                'shippingOptions': 1,
                                                'name': $(this).find("span.cropped_company").text(),
                                                'lat': $(this).find("span.cropped_lat").text(),
                                                'lng': $(this).find("span.cropped_lng").text(),
                                                'street': $(this).find("span.cropped_street").text(),
                                                'houseNumber': $(this).find("span.cropped_housenumber").text(),
                                                'city': $(this).find("span.cropped_city").text(),
                                                'postal': $(this).find("span.cropped_postal").text(),
                                                'country': $(this).find("span.cropped_country").text(),
                                                'description': $(this).find("span.cropped_description").text(),
                                                'image': site_url + '/images/' + $(this).find("span.cropped_image_class").text() + '.png',
                                                'price': $(this).find("span.cropped_price").text(),
                                                'priceformatted': $(this).find("span.cropped_price").text().replace(".", ","),
                                                'openingtimes': openingtimes,
                                                'raw': 1,
                                            }
                                        );

                                        if ($('.cat-' + $(this).find("span.cropped_shipper").text() + '').length === 0) {
                                            var html = '<li class="cat-' + $(this).find("span.cropped_shipper").text() + '"><label><input checked="checked" type="checkbox" name="category" value="' + $(this).find("span.cropped_shipper").text() + '"> ' + $(this).find("span.cropped_description_storelocator").text() + '</label></li>';
                                            $('#category-filters').append(html);
                                        }
                                    }
                                );

                                var config = {
                                    //'debug': true,
                                    'pagination': false,
                                    'infowindowTemplatePath': site_url + '/template/checkout/storelocator/infowindow-description.html',
                                    'listTemplatePath': site_url + '/template/checkout/storelocator/location-list-description.html',
                                    'distanceAlert': -1,
                                    'dataType': "json",
                                    'dataRaw': JSON.stringify(markers, null, 2),
                                    'slideMap': false,
                                    'inlineDirections': false,
                                    'originMarker': true,
                                    'dragSearch': false,
                                    'defaultLoc': true,
                                    'defaultLat': $("#montapacking_latitude").val(),
                                    'defaultLng': $("#montapacking_longitude").val(),
                                    'lengthUnit': 'km',
                                    'exclusiveFiltering': true,
                                    'taxonomyFilters': {
                                        'category': 'category-filters',
                                    },
                                    catMarkers: {
                                        'PAK': [site_url + '/images/PostNL.png', 32, 32],
                                        'DHLservicepunt': [site_url + '/images/DHL.png', 32, 32],
                                        'DPDparcelstore': [site_url + '/images/DPD.png', 32, 32]
                                    },
                                    callbackMarkerClick: function (marker, markerId, $selectedLocation, location) {
                                        $(".bh-sl-container .bh-sl-filters-container .select-item").css("display", "block");
                                        $(".pickup-option[data-markerid=" + location.listid + "]").find(".initialPickupRadio").trigger("click");
                                    },
                                    callbackListClick: function (markerId, selectedMarker, location) {
                                        var selected_input = location.code;

                                        $(".bh-sl-container .bh-sl-filters-container .select-item").css("display", "block");
                                        $(".pickup-option[data-markerid=" + location.listid + "]").find(".initialPickupRadio").trigger("click");
                                    },
                                    callbackNotify: function (notifyText) {

                                    },


                                };


                                useLocator.storeLocator(config);

                                var html = $("#storelocator_container").html();
                                self.showPopup(html);


                            }


                        }
                    );


                },

            }
        );
    }
);
