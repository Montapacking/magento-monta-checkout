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
                    window.monta_plugin_pickup = this;


                    var checkoutConfig = window.checkoutConfig;
                    var montapacking = checkoutConfig.quoteData.montapacking_montacheckout_data;


                    if (montapacking !== undefined) {
                        montapacking = JSON.parse(montapacking);

                        var delivery_information = montapacking.additional_info[0];
                        this.deliveryInformation(delivery_information);

                        var delivery_options = montapacking.details[0].options;

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
                        } else if ($("#montapacking_language").val() == 'DE') {

                            $(delivery_options).each(
                                function (key, value) {

                                    if (value == 'SignatureOnDelivery') {
                                        additional_info.push("Auf Quittung unterschreiben");
                                    } else if (value == 'NoNeighbour') {
                                        additional_info.push("Nicht an die Nachbarn liefern");
                                    } else if (value == 'EveningDelivery') {
                                        additional_info.push("Lieferung am Abend");
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

                            if (window.monta_plugin_pickup.parcelShopAddress() !== null) {
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
