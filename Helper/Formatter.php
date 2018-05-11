<?php

namespace Payever\Payever\Helper;

/**
 * Class Formatter
 * @api
 */
trait Formatter
{
    /**
     * Format price to 0.00 format
     *
     * @param mixed $price
     * @return string
     */
    public function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }
}
