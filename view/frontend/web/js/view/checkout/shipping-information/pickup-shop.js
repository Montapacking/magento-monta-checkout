define(
    [
    'jquery',
    'uiComponent',
    'ko'
    ], function (
        $,
        Component,
        ko
    ) {
        return Component.extend(
            {
                defaults: {
                    template: 'Montapacking_MontaCheckout/shipping-information/pickup-shop',
                    parcelShopAddress: ko.observable(),
                    deliveryInformation: ko.observableArray([]),
                    deliveryOptions: ko.observableArray([]),
                },

                initObservable: function () {
                    var self = this;


                    var checkoutConfig = window.checkoutConfig;
                    var montapacking = checkoutConfig.quoteData.montapacking_montacheckout_data;

                    if (montapacking !== undefined) {
                        montapacking = JSON.parse(montapacking);

                        var delivery_information = montapacking.additional_info[0];
                        this.deliveryInformation(delivery_information);

                        var delivery_options = montapacking.details[1];

                        /*
                         additional_info.push({
                        code: code,
                        name: name,
                        date: date,
                        time: time,
                        price: price,
                        total_price: total_price_raw,
                        });
                         */

                        var additional_info = [];
                        if ($("#montapacking_language").val() == 'NL' || $("#montapacking_language").val() == 'BE') {

                            $(delivery_options).each(
                                function (key, value) {

                                    if (value == 'SignatureOnDelivery') {
                                        additional_info.push("Tekenen bij ontvangst");
                                    } else if (value == 'NoNeighbour') {
                                        additional_info.push("Niet bij de buren afleveren");
                                    } else if (value == 'EveningDelivery') {
                                        additional_info.push("In avond afleveren");
                                    } else {
                                        additional_info.push(value);
                                    }

                                }
                            );

                        } else {
                            $(delivery_options).each(
                                function (key, value) {

                                    if (value == 'SignatureOnDelivery') {
                                        additional_info.push("Signature on delivery");
                                    } else if (value == 'NoNeighbour') {
                                        additional_info.push("No delivery at neighbour");
                                    } else if (value == 'EveningDelivery') {
                                        additional_info.push("Evening delivery");
                                    } else {
                                        additional_info.push(value);
                                    }

                                }
                            );
                        }


                        this.deliveryOptions(additional_info);
                    }


                    this.isSelected = ko.computed(
                        function () {
                            var isSelected = false;

                            if (self.parcelShopAddress() !== null) {
                                isSelected = true;
                            }

                            return isSelected;
                        }, this
                    );

                    this._super();

                    return this;
                }
            }
        );
    }
);
