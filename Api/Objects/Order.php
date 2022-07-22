<?php
namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class Order
 *
 */
class Order
{

    /**
     * @var
     */
    public $total_incl;
    /**
     * @var
     */
    public $total_excl;

    /**
     * Order constructor.
     *
     * @param $incl
     * @param $excl
     */
    public function __construct($incl, $excl)
    {

        $this->setIncl($incl);
        $this->setExcl($excl);
    }

    /**
     * @param $incl
     *
     * @return $this
     */
    public function setIncl($incl)
    {
        $this->total_incl = $incl;
        return $this;
    }

    /**
     * @param $excl
     *
     * @return $this
     */
    public function setExcl($excl)
    {
        $this->total_excl = $excl;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        $order = [
            'OrderValueInclVat' => $this->total_incl,
            'OrderValueExclVat' => $this->total_excl,
        ];

        return $order;
    }
}
