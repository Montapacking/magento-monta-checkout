<?php
namespace Montapacking\MontaCheckout\Api\Objects;

use Montapacking\MontaCheckout\Api\Objects\ShippingOption as MontaCheckout_ShippingOption;

/**
 * Class TimeFrame
 *
 */
class TimeFrame
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
     * @var
     */
    public $description;
    /**
     * @var array
     */
    public $options = [];
    
    public $type;

    /**
     * TimeFrame constructor.
     *
     * @param $from
     * @param $to
     * @param $code
     * @param $description
     * @param $options
     */
    public function __construct($from, $to, $code, $description, $options, $type)
    {

        $this->setFrom($from);
        $this->setTo($to);
        $this->setCode($code);
        $this->setDescription($description);
        $this->setOptions($options);
        $this->setType($type);
    }

    public function setType($type)
    {
        $this->type = $type;
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
     * @param $options
     *
     * @return $this
     */
    public function setOptions($options)
    {

        $list = null;

        if (is_array($options)) {

            foreach ($options as $onr => $option) {

                $list[$onr] = new MontaCheckout_ShippingOption(
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
                    $option->ShippingDeadline,
                    $option->IsPreferred,
                    $option->DisplayName,
                    $option->DiscountPercentage
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
