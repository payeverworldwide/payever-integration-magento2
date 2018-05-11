<?php

namespace Payever\Payever\Api\Data;

interface CreatePaymentResponseInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getError();
}
