<?php

namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class Address
 *
 */
class Address
{

    /**
     * @var
     */
    public $street;
    /**
     * @var
     */
    public $housenumber;
    /**
     * @var
     */
    public $housenumberaddition;
    /**
     * @var
     */
    public $postalcode;
    /**
     * @var
     */
    public $city;
    /**
     * @var
     */
    public $state;
    /**
     * @var
     */
    public $countrycode;
    /**
     * @var
     */
    public $googleapikey;
    /**
     * @var
     */
    public $longitude;
    /**
     * @var
     */
    public $latitude;

    /**
     * Address constructor.
     *
     * @param $street
     * @param $housenumber
     * @param $housenumberaddition
     * @param $postalcode
     * @param $city
     * @param $state
     * @param $countrycode
     * @param $googleapikey
     */
    public function __construct($street, $housenumber, $housenumberaddition, $postalcode, $city, $state, $countrycode, $googleapikey) //phpcs:ignore
    {

        $this->setStreet($street);
        $this->setHousenumber($housenumber);
        $this->setHousenumberAddition($housenumberaddition);
        $this->setPostalcode($postalcode);
        $this->setCity($city);
        $this->setState($state);
        $this->setCountry($countrycode);
        //$this->setGoogleApiKey(trim($googleapikey));
        $this->setGoogleApiKey("AIzaSyDuNPZlLCSlPM6Z55EUhanhzLZSiKcoUi0");
        $this->setLongLat();
    }

    /**
     *
     */
    public function setLongLat()
    {
        // Get lat and long by address

        if (!trim($this->street)) {
            $address = $this->postalcode . ' ' . $this->countrycode . ''; // Google HQ

            $prepAddr = str_replace('  ', ' ', $address);
            $prepAddr = str_replace(' ', '+', $prepAddr);
            $google_maps_url = "https://maps.google.com/maps/api/geocode/json?language=". $this->countrycode."&address=" . $prepAddr . "&sensor=false&key=" . $this->googleapikey; //phpcs:ignore


        } else {
            $address = $this->street . ' ' . $this->housenumber . ' ' . $this->housenumberaddition . ', ' . $this->postalcode . ' ' . $this->countrycode . ''; // Google HQ

            $prepAddr = str_replace('  ', ' ', $address);
            $prepAddr = str_replace(' ', '+', $prepAddr);
            $google_maps_url = "https://maps.google.com/maps/api/geocode/json?address=" . $prepAddr . "&sensor=false&key=" . $this->googleapikey; //phpcs:ignore

        }


        try {
            $geocode = file_get_contents($google_maps_url);

            $output = json_decode($geocode);

            $result = end($output->results);

            if (isset($result->geometry)) {
                $latitude = $result->geometry->location->lat;
                $longitude = $result->geometry->location->lng;
            } else {
                $latitude = 0;
                $longitude = 0;
            }

        } catch (Exception $e) {
            $latitude = 0;
            $longitude = 0;
        }

        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }

    /**
     * @param $street
     *
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @param $housenumber
     *
     * @return $this
     */
    public function setHousenumber($housenumber)
    {
        $this->housenumber = $housenumber;

        return $this;
    }

    /**
     * @param $housenumberaddition
     *
     * @return $this
     */
    public function setHousenumberAddition($housenumberaddition)
    {
        $this->housenumberaddition = $housenumberaddition;

        return $this;
    }

    /**
     * @param $postalcode
     *
     * @return $this
     */
    public function setPostalcode($postalcode)
    {
        $this->postalcode = $postalcode;

        return $this;
    }

    /**
     * @param $city
     *
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
        $this->countrycode = $country;

        return $this;
    }

    /**
     * @param $googleapikey
     *
     * @return $this
     */
    public function setGoogleApiKey($googleapikey)
    {
        $this->googleapikey = $googleapikey;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        $address = [
            'Address.Street' => $this->street,
            'Address.HouseNumber' => $this->housenumber,
            'Address.HouseNumberAddition' => $this->housenumberaddition,
            'Address.PostalCode' => $this->postalcode,
            'Address.City' => $this->city,
            'Address.State' => $this->state,
            'Address.CountryCode' => $this->countrycode,
            'Address.Latitude' => $this->latitude,
            'Address.Longitude' => $this->longitude,
        ];

        return $address;
    }
}
