<?php

namespace Montapacking\MontaCheckout\Api;

use Montapacking\MontaCheckout\Api\Objects\Address as MontaCheckout_Address;
use Montapacking\MontaCheckout\Api\Objects\Order as MontaCheckout_Order;
use Montapacking\MontaCheckout\Api\Objects\Product as MontaCheckout_Product;
use Montapacking\MontaCheckout\Api\Objects\Shipper;
use Montapacking\MontaCheckout\Api\Objects\TimeFrame as MontaCheckout_TimeFrame;
use Montapacking\MontaCheckout\Api\Objects\TimeFrame as MontaCheckout_PickupPoint;
use GuzzleHttp\Client;

/**
 * Class MontapackingShipping
 *
 */
class MontapackingShipping
{

    /**
     * @var string
     */
    private $_user = '';
    /**
     * @var string
     */
    private $_pass = '';

    /**
     * @var string
     */
    private $_googlekey = '';

    /**
     * @var array
     */
    private $_basic = null;
    /**
     * @var null
     */
    private $_order = null;
    /**
     * @var null
     */
    private $_shippers = null;
    /**
     * @var null
     */
    private $_products = null;
    /**
     * @var null
     */
    private $_allowedshippers = null;
    /**
     * @var bool
     */
    private $_onstock = true;
    /**
     * @var null
     */
    public $address = null;

    /**
     * @var null
     */
    private $_logger = null;

    /**
     * @var
     */
    private $_carrierConfig;

    /**
     * MontapackingShipping constructor.
     *
     * @param      $origin
     * @param      $user
     * @param      $pass
     * @param      $googlekey
     * @param      $language
     * @param bool $test
     */
    public function __construct($origin, $user, $pass, $googlekey, $language, $test = false)
    {
        $this->_user = $user;
        $this->_pass = $pass;
        $this->_googlekey = $googlekey;

        $this->_basic = [
            'Origin' => $origin,
            'Currency' => 'EUR',
            'Language' => $language,
        ];
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @param $config
     */
    public function setCarrierConfig($config)
    {
        $this->_carrierConfig = $config;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {

        $result = $this->call('info', ['_basic']);

        if (null === $result) {
            return false;
        }
        return true;
    }

    /**
     * @param $value
     */
    public function setOnstock($value)
    {
        $this->_onstock = $value;
    }

    /**
     * @return bool
     */
    public function getOnstock()
    {
        return $this->_onstock;
    }

    /**
     * @param $total_incl
     * @param $total_excl
     */
    public function setOrder($total_incl, $total_excl)
    {

        $this->_order = new MontaCheckout_Order($total_incl, $total_excl);
    }

    /**
     * @param $street
     * @param $housenumber
     * @param $housenumberaddition
     * @param $postalcode
     * @param $city
     * @param $state
     * @param $countrycode
     */
    public function setAddress($street, $housenumber, $housenumberaddition, $postalcode, $city, $state, $countrycode)
    {

        $this->address = new MontaCheckout_Address(
            $street,
            $housenumber,
            $housenumberaddition,
            $postalcode,
            $city,
            $state,
            $countrycode,
                $this->_googlekey
        );
    }

    /**
     * @param null $shippers
     */
    public function setShippers($shippers = null)
    {

        if (is_array($shippers)) {
            $this->_shippers = $shippers;
        } else {
            $this->_shippers[] = $shippers;
        }
    }

    /**
     * @param     $sku
     * @param     $quantity
     * @param int $length
     * @param int $width
     * @param int $height
     * @param int $weight
     */
    public function addProduct($sku, $quantity, $length = 0, $width = 0, $height = 0, $weight = 0)
    {

        $this->_products['products'][] = new MontaCheckout_Product($sku, $length, $width, $height, $weight, $quantity);
    }

    /**
     * @return array|null
     */
    public function getShippers()
    {

        $shippers = null;

        $result = $this->call('info', ['_basic']);
        if (isset($result->Origins)) {

            $origins = is_array($result->Origins) ? $result->Origins : [$result->Origins];

            // Array goedzetten
            if (is_array($result->Origins)) {
                $origins = $result->Origins;
            } else {
                $origins[] = $result->Origins;
            }

            // Shippers omzetten naar shipper object
            foreach ($origins as $origin) {

                // Check of shipper options object er is
                if (isset($origin->ShipperOptions)) {

                    foreach ($origin->ShipperOptions as $shipper) {

                        $shippers[] = new Shipper(
                                $shipper->ShipperDescription,
                                $shipper->ShipperCode
                        );

                    }

                }

            }

            return $origins;

        }

        return $shippers;
    }

    /**
     * @param $sku
     *
     * @return bool
     */
    public function checkStock($sku)
    {
        $url = "https://api.montapacking.nl/rest/v5/";
        $this->_pass = htmlspecialchars_decode($this->_pass);

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $url,
            // You can set any number of default request options.
            'timeout' => 10.0,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->_user . ":" . $this->_pass)
            ]
        ]);

        $response = $client->get("product/" . $sku . "/stock");
        $result = json_decode($response->getBody(), true);


        if (null !== $result && property_exists($result, 'Message') && $result->Message == 'Zero products found with sku ' . $sku) {
            return false;
        } else if (null !== $result && property_exists($result, 'Stock') && $result->Stock->StockAvailable <= 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param bool $onstock
     * @param bool $mailbox
     * @param bool $mailboxfit
     * @param bool $trackingonly
     * @param bool $insurance
     *
     * @return array
     */

    /**
     * @param bool $onstock
     * @param bool $mailbox
     * @param bool $mailboxfit
     * @param bool $trackingonly
     * @param bool $insurance
     *
     * @return array
     */
    public function getShippingOptions($onstock = true, $mailbox = false, $mailboxfit = false, $trackingonly = false, $insurance = false) //phpcs:ignore
    {
        $timeframes = [];
        $pickups = [];

        if (trim($this->address->postalcode) && (trim($this->address->housenumber) || trim($this->address->street))) {
            // Basis gegevens uitbreiden met shipping option specifieke data
            $this->_basic = array_merge(
                $this->_basic,
                [
                    'ProductsOnStock' => ($onstock) ? 'TRUE' : 'FALSE',
                    'MailboxShipperMandatory' => $mailbox,
                    'TrackingMandatory' => $trackingonly,
                    'InsuranceRequired' => $insurance,
                    'ShipmentFitsThroughDutchMailbox' => $mailboxfit,
                ]
            );

            if ($this->_carrierConfig->getDisablePickupPoints()) {
                $this->_basic = array_merge(
                    $this->_basic,
                    [
                        'MaxNumberOfPickupPoints' => 0
                    ]
                );
            }

            // Timeframes omzetten naar bruikbaar object
            $result = $this->call('ShippingOptions', ['_basic', '_shippers', '_order', 'address', '_products']);
            //$result = json_decode('{"Warnings":[],"Timeframes":[{"From":null,"To":null,"Recommended":true,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"Unknown","ShippingOptions":[{"Code":"MultipleShipper_ShippingDayUnknown","ShipperCodes":["PostNL","PostNlBuspakje","UPS","DHLParcelConnect","PostNLGroot","SEL","SELBuspakje"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{},"Description":"Standard Shipment","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":null,"To":null,"SortDate":null,"Recommended":true,"Options":[],"ShippingDeadline":null,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"Unknown"}],"PickupPointDetails":null,"IsPickupPoint":false},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_176886","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"176886","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"176886","DistanceMeters":574.0,"Company":"Primera","Street":"Zwanenveld","HouseNumber":"9095","PostalCode":"6538SG","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.79750783,"Latitude":51.82364305,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"11:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_217332","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"217332","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"217332","DistanceMeters":598.0,"Company":"PostNL Combinatie Nijmegen","Street":"Holtakkerweg","HouseNumber":"2","PostalCode":"6537TN","District":null,"City":"NIJMEGEN","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.78625087,"Latitude":51.82189318,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"13:00","To":"19:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"13:00","To":"19:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"13:00","To":"19:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"13:00","To":"19:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"13:00","To":"19:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_221450","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"221450","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"221450","DistanceMeters":919.0,"Company":"Spar Lindenholt","Street":"St. Agnetenweg","HouseNumber":"63","PostalCode":"6545AT","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.80248697,"Latitude":51.83035788,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"08:00","To":"19:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_167564","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"167564","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"167564","DistanceMeters":2353.0,"Company":"GAMMA Nijmegen-Nwe Dukenburgseweg","Street":"Nieuwe Dukenburgseweg","HouseNumber":"11","PostalCode":"6534AD","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.82378309,"Latitude":51.82131906,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Sunday","OpeningTimes":[{"From":"12:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_210050","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"210050","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"210050","DistanceMeters":3086.0,"Company":"Jumbo (5112) Hatertseweg Nijmegen","Street":"Hatertseweg","HouseNumber":"835","PostalCode":"6535ZT","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.82397294,"Latitude":51.80798843,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"08:00","To":"20:00"}]},{"Day":"Sunday","OpeningTimes":[{"From":"11:00","To":"18:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_161725","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"161725","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"161725","DistanceMeters":3159.0,"Company":"Bruna Teunissen","Street":"Hatertseweg","HouseNumber":"829","PostalCode":"6535ZT","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.82473697,"Latitude":51.80752717,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"10:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_167563","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"167563","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"167563","DistanceMeters":3547.0,"Company":"GAMMA Nijmegen-Energieweg","Street":"Energieweg","HouseNumber":"22 -24","PostalCode":"6541CX","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.82841314,"Latitude":51.84829409,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"18:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_208425","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"208425","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"208425","DistanceMeters":3630.0,"Company":"CoopCompact","Street":"Laan 1945","HouseNumber":"22","PostalCode":"6551CZ","District":null,"City":"Weurt","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.81398616,"Latitude":51.8558438,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"08:00","To":"19:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"08:00","To":"18:00"}]},{"Day":"Sunday","OpeningTimes":[{"From":"12:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_171996","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"171996","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"171996","DistanceMeters":3777.0,"Company":"Karwei","Street":"Industrieweg","HouseNumber":"103 A","PostalCode":"6541TV","District":null,"City":"Nijmegen","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.83376166,"Latitude":51.84760871,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Sunday","OpeningTimes":[{"From":"09:00","To":"18:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_167169","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"167169","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"167169","DistanceMeters":3830.0,"Company":"Gamma","Street":"Claudiuslaan","HouseNumber":"62","PostalCode":"6642AG","District":null,"City":"Beuningen Gld","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.75332517,"Latitude":51.85217472,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Sunday","OpeningTimes":[{"From":"12:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_215938","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"215938","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"215938","DistanceMeters":3874.0,"Company":"ALLSAFE Mini Opslag Nijmegen","Street":"Industrieweg","HouseNumber":"46","PostalCode":"6541TW","District":null,"City":"NIJMEGEN","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.83474042,"Latitude":51.84825215,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_174694","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"174694","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"174694","DistanceMeters":4000.0,"Company":"Primera","Street":"Thorbeckeplein","HouseNumber":"13","PostalCode":"6641CB","District":null,"City":"Beuningen Gld","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.77011422,"Latitude":51.86023767,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"11:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"08:30","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"08:30","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"08:30","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"08:30","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"08:30","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_163450","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"163450","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"163450","DistanceMeters":4095.0,"Company":"Bruna Wito","Street":"Julianaplein","HouseNumber":"28 -30","PostalCode":"6641CT","District":null,"City":"Beuningen Gld","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.77141647,"Latitude":51.86143786,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"11:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"21:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"17:00"}]}]},"IsPickupPoint":true},{"From":"2023-03-08T15:00","To":"2023-03-22T00:00","Recommended":false,"TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe","ShippingOptions":[{"Code":"PAK_216210","ShipperCodes":["PAK"],"ShipperOptionCodes":[],"ShipperOptionsWithValue":{"PickupPointCode":"216210","PickupPointRetailID":"PNPNL-01"},"Description":"Pakje gemak van PostNL","IsMailbox":false,"SellPrice":5.95,"SellPriceWithoutDiscount":5.95,"SellPriceCurrency":"EUR","VATRate":21.00,"From":"2023-03-08T15:00","To":"2023-03-22T00:00","SortDate":"2023-03-08T15:00","Recommended":true,"Options":[],"ShippingDeadline":"2023-03-07T23:59","TypeCode":null,"TypeDescription":null,"FromToTypeCode":"DeliveryTimeframe"}],"PickupPointDetails":{"Code":"216210","DistanceMeters":4556.0,"Company":"Mabo","Street":"Tunnelweg","HouseNumber":"44","PostalCode":"6601CX","District":null,"City":"WIJCHEN","State":null,"CountryCode":"NL","AddressRemark":null,"Phone":null,"Longitude":5.72779183,"Latitude":51.8134486,"ImageUrl":null,"OpeningTimes":[{"Day":"Monday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Tuesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Wednesday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Thursday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Friday","OpeningTimes":[{"From":"09:00","To":"18:00"}]},{"Day":"Saturday","OpeningTimes":[{"From":"09:00","To":"17:00"}]}]},"IsPickupPoint":true}],"ImpossibleShipperOptions":[{"ShipperCode":"PostNLGroot","Reasons":[{"Code":"SmallerVariantExists","Reason":"The smaller equivalent of this shipper is already allowed. Bigger is not necessary."}]},{"ShipperCode":"DHLParcelConnect","Reasons":[{"Code":"Region","Reason":"Momenteel niet mogelijk voor Nederland."}]}],"Notices":[{"ShipperCode":"AFH","Message":"Not available: no price assigned"},{"ShipperCode":"SEL","Message":"Not available: no price assigned"},{"ShipperCode":"DHLpallet","Message":"Not available: no price assigned"},{"ShipperCode":"UPS","Message":"Not available: no price assigned"},{"ShipperCode":"DPD","Message":"Not available: no price assigned"},{"ShipperCode":"PostNL","Message":"Not available: no price assigned"}]}');
            if (isset($result->Timeframes)) {
                // Shippers omzetten naar shipper object
                foreach ($result->Timeframes as $timeframe) {
                    if (!$timeframe->IsPickupPoint) {
                        $timeframes[] = new MontaCheckout_TimeFrame(
                                $timeframe->From,
                                $timeframe->To,
                                $timeframe->TypeCode,
                                $timeframe->TypeDescription,
                                $timeframe->ShippingOptions,
                                $timeframe->FromToTypeCode,   
                                $timeframe->DiscountPercentage
                        );
                    } else {
                        $pickups[] = new MontaCheckout_PickupPoint(
                            $timeframe->From,
                            $timeframe->To,
                            $timeframe->TypeCode,
                            $timeframe->PickupPointDetails,
                            $timeframe->ShippingOptions,
                            $timeframe->FromToTypeCode
                    );
                    }
                }

            }
        }

        return ['DeliveryOptions' => $timeframes, 'PickupOptions' => $pickups];
    }

    /**
     * @param      $method
     * @param null $send
     *
     * @return mixed
     */
    public function call($method, $send = null)
    {

        $request = '?';
        if ($send != null) {

            // Request neede data
            foreach ($send as $data) {

                if (isset($this->{$data}) && $this->{$data} != null) {

                    if (!is_array($this->{$data})) {

                        $request .= '&' . http_build_query($this->{$data}->toArray());

                    } else {
                        $request .= '&' . http_build_query($this->{$data});

                    }

                }

            }

        }

        $url = "https://api.montapacking.nl/rest/v5/";
        $this->_pass = htmlspecialchars_decode($this->_pass);

        $client = new Client([
            'base_uri' => $url,
            'timeout' => 10.0,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->_user . ":" . $this->_pass)
            ]
        ]);

        $method = strtolower($method);
        $response = $client->get($method . '?' . $request);
        $result = json_decode($response->getBody());

        if ($response->getStatusCode() != 200) {
            $error_msg = $response->getReasonPhrase() . ' : ' . $response->getBody();
            $logger = $this->_logger;
            $context = ['source' => 'Montapacking Checkout'];
            $logger->critical($error_msg . " (" . $url . ")", $context);
            $result = null;
        }

        if ($result == null) {

            sleep(1);
            $response = $client->get($method . '?' . $request);

            if ($response->getStatusCode() != 200) {
                $error_msg = $response->getReasonPhrase() . ' : ' . $response->getBody();
                $logger = $this->_logger;
                $context = ['source' => 'Montapacking Checkout'];
                $logger->critical($error_msg . " (" . $url . ")", $context);
                $result = null;
            }
        }

        $url = "https://api.montapacking.nl/rest/v5/" . $method . $request; 

        if (null !== $this->_logger && null === $result) {
            $logger = $this->_logger;
            $context = ['source' => 'Monta Checkout'];
            $logger->critical("Webshop was unable to connect to Monta REST api. Please check your username and password. Otherwise please contact Montapacking (" . $url . ")", $context); //phpcs:ignore
        } elseif (null !== $this->_logger) {
            $logger = $this->_logger;
            $context = ['source' => 'Monta Checkout'];
            $logger->notice("Connection logged (" . $url . ")", $context);
        }

        if ($this->_carrierConfig->getLogErrors()) {

            if (null !== $this->_logger && isset($result->Warnings)) {

                foreach ($result->Warnings as $warning) {

                    $logger = $this->_logger;
                    $context = ['source' => 'Montapacking Checkout'];

                    if (null !== $warning->ShipperCode) {
                        $logger->notice($warning->ShipperCode . " - " . $warning->Message, $context);
                    } else {
                        $logger->notice($warning->Message, $context);
                    }

                }
            }

            if (null !== $this->_logger && isset($result->Notices)) {

                foreach ($result->Notices as $notice) {
                    $logger = $this->_logger;
                    $context = ['source' => 'Montapacking Checkout'];

                    if (null !== $notice->ShipperCode) {
                        $logger->notice($notice->ShipperCode . " - " . $notice->Message, $context);
                    } else {
                        $logger->notice($notice->Message, $context);
                    }

                }
            }

            if (null !== $this->_logger && isset($result->ImpossibleShipperOptions)) {

                foreach ($result->ImpossibleShipperOptions as $impossibleoption) {
                    foreach ($impossibleoption->Reasons as $reason) {

                        $logger = $this->_logger;
                        $context = ['source' => 'Montapacking Checkout'];
                        $logger->notice($impossibleoption->ShipperCode . " - " . $reason->Code . " | " . $reason->Reason, $context); //phpcs:ignore
                    }
                }

            }

        }

        return $result;
    }
}
