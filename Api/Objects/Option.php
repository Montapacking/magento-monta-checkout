<?php
namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class Option
 *
 * @package Montapacking\MontaCheckout\Api\Objects
 */
class Option
{

    /**
     * @var
     */
    public $code;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $price;
    /**
     * @var
     */
    public $currency;

    /**
     * Option constructor.
     *
     * @param $code
     * @param $name
     * @param $price
     * @param $currency
     */
    public function __construct($code, $name, $price, $currency)
    {

        $this->setCode($code);
        $this->setName($name);
        $this->setPrice($price);
        $this->setCurrency($currency);

    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {

        $this->name = $name;

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
