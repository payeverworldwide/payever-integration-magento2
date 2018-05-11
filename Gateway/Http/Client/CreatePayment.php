<?php

namespace Payever\Payever\Gateway\Http\Client;

/**
 * Class CreatePayment
 * @package Payever\Payever\Gateway\Http\Client
 */
class CreatePayment extends AbstractClient
{
    /**
     * Execute create payment request
     *
     * @param array $data
     * @return mixed
     */
    protected function process(array $data)
    {
        return $this->adapter->createPayment($data);
    }
}
