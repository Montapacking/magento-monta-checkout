<?php
namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class PickupPoint
 *
 */
class PickupPoint
{

    /**
     * @var
     */
    public $from;
    /**
     * @var
     */
    public $to;
    /**
     * @var
     */
    public $code;
    /**
     * @var array
     */
    public $details = [];
    /**
     * @var array
     */
    public $options = [];

    /**
     * PickupPoint constructor.
     *
     * @param $from
     * @param $to
     * @param $code
     * @param $details
     * @param $options
     */
    public function __construct($from, $to, $code, $details, $options)
    {

        $this->setFrom($from);
        $this->setTo($to);
        $this->setCode($code);
        $this->setDetails($details);
        $this->setOptions($options);
    }

    /**
     * @param $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @param $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @param $details
     *
     * @return $this
     */
    public function setDetails($details)
    {

        $pickup = null;
        if (is_object($details)) {

            $today = date("l");
            $times = $details->OpeningTimes;

            $arr = [];
            foreach ($times as $key => $values) {
                if ($values->Day == $today) {
                    foreach ($values->OpeningTimes as $timekey => $times) {

                        $array = [];
                        $array['from'] = $times->From;
                        $array['to'] = $times->To;
                        $arr[] = $array;
                    }
                }
            }

            $pickup = (object)[
                'code' => $details->Code,
                'name' => $details->Company,
                'street' => $details->Street,
                'houseNumber' => $details->HouseNumber,
                'zipcode' => $details->PostalCode,
                'place' => $details->City,
                'country' => $details->CountryCode,
                'phone' => $details->Phone,
                'distance' => $details->DistanceMeters,
                'lat' => $details->Latitude,
                'lng' => $details->Longitude,
                'openingtimes' => json_encode($arr),
                'image' => $details->ImageUrl,
                'image_replace' => str_replace(",", "_", $details->ImageUrl)
            ];

        }

        $this->details = $pickup;

        return $this;
    }

    /**
     * @param $options
     *
     * @return $this
     */
    public function setOptions($options)
    {

        $list = null;

        if (is_array($options)) {

            foreach ($options as $option) {

                $list[] = new MontaCheckout_ShippingOption(
                    $option->Code,
                    $option->ShipperCodes,
                    $option->ShipperOptionCodes,
                    $option->ShipperOptionsWithValue,
                    $option->Description,
                    $option->IsMailbox,
                    $option->SellPrice,
                    $option->SellPriceCurrency,
                    $option->From,
                    $option->To,
                    $option->Options,
                    $option->ShippingDeadline
                );

            }

        }

        $this->options = $list;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        $option = null;
        foreach ($this as $key => $value) {
            $option[$key] = $value;
        }

        return $option;
    }
}
