<?php
namespace Montapacking\MontaCheckout\Api\Objects;

use Montapacking\MontaCheckout\Api\Objects\Option as MontaCheckout_Option;

/**
 * Class ShippingOption
 *
 * @package Montapacking\MontaCheckout\Api\Objects
 */
class ShippingOption
{

    /**
     * @var
     */
    public $code;
    /**
     * @var
     */
    public $codes;
    /**
     * @var
     */
    public $optioncodes;
    /**
     * @var
     */
    public $optionsWithValue;
    /**
     * @var
     */
    public $description;
    /**
     * @var
     */
    public $mailbox;
    /**
     * @var
     */
    public $price;
    /**
     * @var
     */
    public $currency;
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
    public $extras;
    /**
     * @var
     */
    public $date;

    /**
     * ShippingOption constructor.
     *
     * @param $code
     * @param $codes
     * @param $optioncodes
     * @param $optionsWithValue
     * @param $description
     * @param $mailbox
     * @param $price
     * @param $currency
     * @param $from
     * @param $to
     * @param $extras
     * @param $date
     */
    public function __construct($code, $codes, $optioncodes, $optionsWithValue, $description, $mailbox, $price, $currency, $from, $to, $extras, $date) //phpcs:ignore
    {

        $this->setCode($code);
        $this->setCodes($codes);
        $this->setOptionCodes($optioncodes);
        $this->setOptionsWithValue($optionsWithValue);
        $this->setDescription($description);
        $this->setMailbox($mailbox);
        $this->setPrice($price);
        $this->setCurrency($currency);
        $this->setFrom($from);
        $this->setTo($to);
        $this->setExtras($extras);
        $this->setDate($date);
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
     * @param $codes
     *
     * @return $this
     */
    public function setCodes($codes)
    {
        $this->codes = $codes;
        return $this;
    }

    /**
     * @param $optioncodes
     *
     * @return $this
     */
    public function setOptionCodes($optioncodes)
    {
        $this->optioncodes = $optioncodes;
        return $this;
    }

    /**
     * @param $optionsWithValue
     *
     * @return $this
     */
    public function setOptionsWithValue($optionsWithValue)
    {
        $this->optionsWithValue = $optionsWithValue;
        return $this;
    }

    /**
     * @param $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param $mailbox
     *
     * @return $this
     */
    public function setMailbox($mailbox)
    {
        $this->mailbox = $mailbox;
        return $this;
    }

    /**
     * @param $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @param $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
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
     * @param $extras
     *
     * @return $this
     */
    public function setExtras($extras)
    {

        $list = null;

        if (is_array($extras)) {

            foreach ($extras as $extra) {

                $list[] = new MontaCheckout_Option(
                    $extra->Code,
                    $extra->Description,
                    $extra->SellPrice,
                    $extra->SellPriceCurrency
                );

            }

        }

        $this->extras = $list;
        return $this;
    }

    /**
     * @param $date
     */
    public function setDate($date)
    {
        if ($date != null) {
            $this->date = date('Y-m-d H:i:s', strtotime($date));
        }
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
