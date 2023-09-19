define(
    [
        'ko',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'Magento_Customer/js/model/customer'
    ], function (
        ko,
        quote,
        $,
        customer
    ) {
        'use strict';

        var address = {
                postcode    : null,
                country     : null,
                street      : null,
                city        : null,
                firstname   : null,
                lastname    : null,
                telephone   : null,
                housenumber : null,
                housenumberaddition : null
            },
            countryCode,
            timer,
            allFieldsExists = true,
            valueUpdateNotifier = ko.observable(null);

      
            var fields = [
                "input[name*='streetNumber']",
                "input[name*='strNumberAddition']",
                "input[name*='postalCode']",
                "select[name*='country_id']",
                "input[name*='street[0]']",
                "input[name*='street[1]']",
                "input[name*='street[2]']",
                "input[name*='city']",
                "input[name*='postcode']",
            ];


        /**
         * Without cookie data Magento is not observing the fields so the AddressFinder is never triggered.
         * The Timeout is needed so it gives the Notifier the chance to retrieve the correct country code,
         * and not the default value.
         */


        (function() {
            function checkState() {

            	var countryCheck = "";
            	if(typeof $("select[name*='country_id']").val() !== "undefined")
            	{
            		countryCheck = $("select[name*='country_id']").val();
            	}

                if ($("input[name*='postalCode']").length > 0 && countryCheck == "NL") {

                    if ($("input[name*='postalCode']").length > 0 && $("#montapacking-plugin").length) {
                        var success = true; // do something to check the state
                    } else {
                        var success = false; // do something to check the state
                    }

                } else {

                    if ($("input[name*='postcode']").length > 0 && $("#montapacking-plugin").length) {
                        var success = true; // do something to check the state
                    } else {
                        var success = false; // do something to check the state
                    }
                }


                if (!success) {
                    setTimeout(checkState, 50);
                }
            }
            setTimeout(checkState, 50);
        })();


        $(document).on('change', fields.join(','), function () {

            // Clear timeout if exists.
            if (typeof timer !== 'undefined') {
                clearTimeout(timer);
            }

            timer = setTimeout(
                function () {
                    countryCode = $("select[name*='country_id']").val();
                    valueUpdateNotifier.notifySubscribers();
                }, 500
            );
        } );

        /**
         * Collect the needed information from the quote
         */

        return ko.computed(
            function () {

                valueUpdateNotifier();

                /**
                 * The street is not always available on the first run.
                 */
                var shippingAddress = quote.shippingAddress();
                if (shippingAddress) {
                    address = {
                        city: shippingAddress.city,
                        postcode: shippingAddress.postcode,
                        lastname: shippingAddress.lastname,
                        firstname: shippingAddress.firstname,
                        telephone: shippingAddress.telephone,
                        country: shippingAddress.countryId
                    };
                }

                address.street = {
                    0 : $("input[name*='street[0]']").val(),
                    1 : $("input[name*='street[1]']").val(),
                    2 : $("input[name*='street[2]']").val()
                };

                if ($("input[name*='streetNumber']").length > 0 && $("input[name*='strNumberAddition']").length > 0){
                    address.housenumber = $("input[name*='streetNumber']").val();
                    address.housenumberaddition   = $("input[name*='strNumberAddition']").val();
                }

                if (!address.country || !address.postcode) {
                    return false;
                }

                return address;


            }.bind(this)
        );
    }
);
