<?php
namespace Montapacking\MontaCheckout\Api\Objects;

/**
 * Class Product
 *
 */
class Product
{

    /**
     * @var
     */
    public $sku;
    /**
     * @var
     */
    public $length;
    /**
     * @var
     */
    public $width;
    /**
     * @var
     */
    public $height;
    /**
     * @var
     */
    public $weight;
    /**
     * @var
     */
    public $quantity;

    /**
     * Product constructor.
     *
     * @param $sku
     * @param $length
     * @param $width
     * @param $height
     * @param $weight
     * @param $quantity
     */
    public function __construct($sku, $length, $width, $height, $weight, $quantity)
    {

        $this->setSku($sku);
        $this->setLength($length);
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setWeight($weight);
        $this->setQuantity($quantity);
    }

    /**
     * @param $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @param $length
     *
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @param $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @param $weight
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @param $quantity
     *
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        $product = [
            'SKU' => $this->sku,
            'LengthMm' => $this->length,
            'WidthMm' => $this->width,
            'HeightMm' => $this->height,
            'WeightGrammes' => $this->weight,
            'Quantity' => $this->quantity,
        ];

        return $product;
    }
}
