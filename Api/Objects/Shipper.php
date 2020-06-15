<?php

namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class Shipper
 *
 * @package Montapacking\MontaCheckout\Api\Objects
 */
class Shipper
{

    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $code;

    /**
     * Shipper constructor.
     *
     * @param $name
     * @param $code
     */
    public function __construct($name, $code)
    {

        $this->setName($name);
        $this->setCode($code);

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
     * @return array
     */
    public function toArray()
    {

        $shipper = [
            'code' => $this->code,
            'name' => $this->name
        ];

        return $shipper;

    }

}
