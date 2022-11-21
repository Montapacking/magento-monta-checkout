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
        'storeLocator',
        'Magento_Checkout/js/model/shipping-rate-registry'
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
        storeLocator,
        rateRegistry
    ) {

        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Montapacking_MontaCheckout/checkout/shipping/additional-block',
                    postcode: null,
                    country: null,
                    hasconnection: 'true',
                    urlPrefix: '',
                    deliveryServices: ko.observableArray([]),
                    standardDeliveryServices: ko.observableArray([]),
                    filteredDeliveryServices: ko.observableArray([]),
                    daysForSelect: ko.observableArray([]),
                    pickupServices: ko.observableArray([]),
                    deliveryFee: ko.observable(),
                    pickupFee: ko.observable(),
                    selectedShippers: ko.observable(),
                    selectedPickup: ko.observable(),

                },

                initObservable: function () {


                    //one step checkout solution, update buttons and quantity change are not working, so we are gonna hide this options
                    require([
                    'jquery',
                    'Magento_Ui/js/lib/view/utils/dom-observer',
                    ], function ($,$do) {
                        $(document).ready(function(){
                            $do.get('.product-item-details .details-qty', function(elem){
                                //$(elem).removeClass('visible');
                                $(elem).find("input").attr('readonly', true);
                                $('.product-item-details .qtybuttons .remove').css('display', 'none');
                                $('.product-item-details .qtybuttons .add').css('display', 'none');
                            });
                        });
                    });

                    self = this;

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

                    this.urlPrefix = urlPrefix;

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
                            'hasconnection',
                            'postcode',
                            'street',
                            'country',
                            'deliveryServices',
                            'filteredDeliveryServices',
                            'standardDeliveryServices',
                            'daysForSelect',
                            'pickupPoints'
                        ]
                    );


                    AddressFinder.subscribe(
                        function (address) {

                            if (typeof address == "undefined") {
                                return;
                            }

                            if (!address || JSON.stringify(address) == $("#old_address").val()) {
                                return;
                            }

                            this.deliveryFee(null);
                            this.pickupFee(null);

                            this.getLongLat(address.street, address.postcode, address.city, address.country, address.housenumber, address.housenumberaddition, false);
                            this.getPickupServices(address.street, address.postcode, address.city, address.country, address.housenumber, address.housenumberaddition, false);
                            this.getDeliveryServices(address.street, address.postcode, address.city, address.country, address.housenumber, address.housenumberaddition);

                            self.toggleTab('.montapacking-tab-pickup', '.montapacking-tab-delivery', '.pickup-services', '.delivery-services', false, true);




                            // fill old adress field
                            var existCondition = setInterval(function() {
                                if ($("#old_address").length) {
                                    clearInterval(existCondition);
                                    $("#old_address").val(JSON.stringify(address));
                                }
                            }, 100);

                        }.bind(this)
                    );

                    self.loadPopup();

                    return this;
                },

                checkState: function () {

                    if ($(".loading-mask").is(":visible")) {
                        var success = false; // do something to check the state
                    } else {
                        var success = true; // do something to check the state
                        $(".montapacking-tab.montapacking-tab-delivery").trigger("click");
                    }

                    if (!success) {
                        setTimeout(self.checkState(), 500);
                    }
                },

                /**
                 * Retrieve LONG LAT
                 */
                getLongLat: function (street, postcode, city, country, housenumber, housenumberaddition, longlat) {

                    $.ajax(
                        {
                            method: 'GET',
                            url: this.urlPrefix+'/montacheckout/deliveryoptions/longlat',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country,
                                housenumber: housenumber,
                                housenumberaddition: housenumberaddition,
                                longlat: longlat
                            }
                        }
                    ).done(


                        function (services) {

                            $("#montapacking_longitude").val(services.longitude);
                            $("#montapacking_latitude").val(services.latitude);
                            $("#montapacking_language").val(services.language);

                            $("#hasconnection").val("y");
                            if (services.hasconnection == 'false') {
                                this.hasconnection(null);
                                $("#hasconnection").val("n");
                            }

                        }.bind(this)
                    );
                },

                /**
                 * Retrieve Delivery Options from Montapacking.
                 */
                getDeliveryServices: function (street, postcode, city, country, housenumber, housenumberaddition) {

                    $.ajax(
                        {
                            method: 'GET',
                            url: this.urlPrefix+'/montacheckout/deliveryoptions/delivery',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country,
                                housenumber: housenumber,
                                housenumberaddition: housenumberaddition
                            }
                        }
                    ).done(
                        function (services) {
                            const objectArray = Object.values(services);
                            this.deliveryServices(objectArray);

                            var filteredDeliveryServicesList = objectArray.filter(timeframe =>  timeframe.options[0].type !== 'Unknown');
                            if(filteredDeliveryServicesList.length > 0){
                                var distinctFilteredItems = self.initDatePicker(objectArray);                                     
                                this.filteredDeliveryServices(filteredDeliveryServicesList.filter(timeframe => 
                                    timeframe.options[0].date === distinctFilteredItems[0].date));
                                
                                // set width of date picker by number of list items 
                                var width = $("ol li").length;
                                $("#slider-content").width(width * 110); 

                                $('#slider-content ol li:first-child').addClass("selected_day"); 
                            }

                            this.standardDeliveryServices(objectArray.filter(timeframe => 
                                timeframe.options[0].from === "" &&  
                                timeframe.options[0].type === 'Unknown'));  

                        }.bind(this)
                    );
                },
                
                initDatePicker: function(objectArray){
                    var distinctFilteredItems = [];
                    
                    //search all shipping options with delivery date, so the dates can be used for the datepicker 
                    var filteredItems  = objectArray.filter(timeframe => timeframe.options[0].type !== "Unknown").map(option => 
                        { return { 
                            "date":option.options[0].date, 
                            "day":option.options[0].date_string.split(' ')[0],
                            "day_string":  
                        option.options[0].date_string.split(' ')[1].concat(' ', option.options[0].date_string.split(' ')[2]) }}); 
                        
                    // filter all duplicates
                    $.each(filteredItems, function (index, item) {
                        var alreadyAdded = false;
                        var i;
                        for (i in distinctFilteredItems) {
                            if (distinctFilteredItems[i].date == item.date) {
                                alreadyAdded = true;
                            }
                        } 
                        if (!alreadyAdded) {
                            distinctFilteredItems.push(item);
                        }
                        //show max 10 days in date picker
                        if(distinctFilteredItems.length == 10) {
                            return false;
                        }
                    });

                    this.daysForSelect(distinctFilteredItems);
                   
                    return distinctFilteredItems;
                },

                /**
                 * Retrieve Delivery Options from Montapacking.
                 */
                getPickupServices: function (street, postcode, city, country, housenumber, housenumberaddition, longlat) {

                    $.ajax(
                        {
                            method: 'GET',
                            url: this.urlPrefix+'/montacheckout/deliveryoptions/pickup',
                            type: 'jsonp',
                            showLoader: true,
                            data: {
                                street: street,
                                postcode: postcode,
                                city: city,
                                country: country,
                                housenumber: housenumber,
                                housenumberaddition: housenumberaddition,
                                longlat: longlat
                            }
                        }
                    ).done(
                        function (services) {
                            const objectArray = Object.values(services);
                            this.pickupServices(objectArray);

                            var counter = 0;

                            // disable extra pickuppoints in view
                            /*
                            $(".montapacking-pickup-service.pickup-option").each(
                                function (index) {
                                    counter++;
                                    $(this).addClass("overruleshow");

                                    if (counter == 3) {
                                        return false;
                                    }

                                }
                            );
                            */
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

                    var address = quote.shippingAddress();


                    if (address.extension_attributes === undefined) {
                        address.extension_attributes = {};
                    }

                    address.extension_attributes.montapacking_montacheckout_data = checkoutConfig.quoteData.montapacking_montacheckout_data;

                    quote.shippingAddress(address);

                },

                getfilterDeliveryServicesByDate: function(date, event) {
                    $('#slider-content ol li').removeClass("selected_day");
                    var target = $(event.target).closest(".day");
                    target.addClass("selected_day");

                    self.setfilterDeliveryServicesByDate(date); 
                },

                setfilterDeliveryServicesByDate: function(date){
                    var objects = this.deliveryServices;
                    var objectsFiltered = objects.filter(timeframe => timeframe.options[0].date === date.date)
                    var objectsSorted=  objectsFiltered.sort((a, b) =>  
                         parseInt(parseFloat(a.options[0].price_raw)) - parseInt(parseFloat(b.options[0].price_raw))
                    )
                    this.filteredDeliveryServices(objectsSorted); 
                },

                moveLeft: function(){
                    if(this.position == null){
                        this.position =  $("#slider").children().position().left;
                    }

                    $("#slider").animate({
                        scrollLeft: this.position - 500
                    }, 500);
                    this.position -= 500;
                },

                moveRight: function(){
                    if(this.position == null){
                        this.position =  $("#slider").children().position().left;
                    }

                    $("#slider").animate({
                        scrollLeft: this.position + 500
                    }, 500);
                    this.position += 500;
                },

                toggleTab: function (previousTab, currentTab, previousContent, currentContent, triggerClick = false, hideDeliverInfo = false) {


                    $(previousTab).removeClass('active');
                    $(currentTab).addClass('active');
                    $(previousContent).hide();
                    $(currentContent).fadeIn('slow');

                    if (triggerClick) {

                        if (currentTab == '.montapacking-tab-pickup') {
                            $("input.selectshipment").val("pickup");
                            $(".pickup-option:first").find("input.initialPickupRadio").trigger("click");
                            $("#date-picker").hide()
                            $("#standard-delivery-services").hide()
                            var address = JSON.parse($("#old_address").val());

                            self.getLongLat(address.street, address.postcode, address.city, address.country, address.housenumber, address.housenumberaddition, true);
                            self.getPickupServices(address.street, address.postcode, address.city, address.country, address.housenumber, address.housenumberaddition, true);

                        } else {

                            $("input.selectshipment").val("delivery");
                            $(".delivery-option:not(.SameDayDelivery):first").find("input[class=montapacking_delivery_option]").trigger("click");
                            $("#standard-delivery-services").show()

                            if ($(".SameDayDelivery").length) {
                                $(".havesameday").removeClass("displaynone");
                            } else {
                                $(".nothavesameday").removeClass("displaynone");
                            }
                        }
                    }

                    if (hideDeliverInfo == true) {
                        $(".delivery-information").hide();
                    }

                },

                showDeliveryOptions: function (informationTab, optionsTab) {
                    $(informationTab).hide();
                    $(optionsTab).fadeIn('slow');
                    $("#date-picker").show();
                },

                selectShipper: function () {

                    $(".delivery-information").hide();
                    $("#date-picker").hide();
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
                    var image_class_replaced = $(this).parents(".delivery-option").find(".cropped_image_class_replaced").text();
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
                    $(".delivery-information").find(".montapacking-container-logo").removeClass().addClass("montapacking-container-logo").addClass(image_class_replaced);

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
                    );


                    $('.delivery-option input[type=checkbox]:checked').not(checked_boxes).attr('checked', false);

                    $(".delivery-information").fadeIn('slow');
                    $(".delivery-option").hide();

                    total_price = total_price.toFixed(2);
                    var total_price_raw = total_price;

                    total_price = total_price.toString().replace('.', ',');

                    setTimeout(
                        function () {
                            $(".table-checkout-shipping-method").find("input[value='montapacking_montapacking']").parents(".row").find("span.price").html("&euro;" + total_price);
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

                    if ($(".SameDayDelivery").length) {
                        $(".havesameday").removeClass("displaynone");
                    } else {
                        $(".nothavesameday").removeClass("displaynone");
                    }

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

                    var n = code_pickup.includes("_packStation");

                    if (n)
                    {
                        $("#PCPostNummer").removeClass("displaynone");

                        $(".open-business-hours").addClass("displaynone");
                        $(".block-business-hours").addClass("displaynone");
                    }
                    else
                    {
                        $("#PCPostNummer").val("");
                        $("#PCPostNummer").addClass("displaynone");

                        $(".open-business-hours").removeClass("displaynone");
                        $(".block-business-hours").removeClass("displaynone");
                    }

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

                    total_price = total_price.toString().replace('.', '.');

                    setTimeout(
                        function () {
                            $(".table-checkout-shipping-method").find("input[value='montapacking_montapacking']").parents(".row").find("span.price").html("&euro;" + total_price);
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
                },

                loadPopup: function (sHtml) {

                    $("body").prepend('<div id="modular-container"/>');
                    $("body").prepend('<div id="modular-background"/>');

                    var html = "<div id=\"storelocator_container\">\n" +
                        "    <div class=\"container\">\n" +
                        "        <div class=\"bh-sl-container\">\n" +
                        "            <div class=\"bh-sl-filters-container\">\n" +
                        "                <button href=\"javascript:;\" data-bind=\"click: closePopup, i18n: 'Use selection'\" class=\"select-item displaynone\"></button>\n" +
                        "                <button type=\"button\" href=\"javascript:;\" data-bind=\"click: closePopup, i18n: 'x'\" class=\"select-item close-item\"></button>\n" + 
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
                        '<div class="positioning">'+html+'</div>'
                    );

                    ko.applyBindings(self, document.getElementById('modular-container'));

                },

                closePopup: function () {

                    $("#modular-container").css("display", "none");
                    $("#modular-background").css("display", "none");
                    return false;

                },

                openStoreLocator: function () {

                    $('body').trigger('processStart');

                    require(
                        ['Handlebars',
                            'jquery',
                            'google',
                            'storeLocator'], function (Handlebars, $, google, storeLocator) {


                            window.Handlebars = Handlebars;

                            var useLocator = $('#bh-sl-map-container');

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

                                setTimeout(function(){
                                    useLocator.storeLocator(config);

                                    var html = $("#storelocator_container").html();
                                    self.showPopup(html);
                                    $('body').trigger('processStop');

                                }, 3000);

                            }
                        }
                    );
                },
            }
        );
    }
);
