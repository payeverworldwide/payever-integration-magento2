<?php

namespace Payever\Payever\Gateway\Http\Client;

/**
 * Class RefundTransaction
 * @package Payever\Payever\Gateway\Http\Client
 */
class RefundTransaction extends AbstractClient
{
    /**
     * @param array $data
     * @return mixed
     */
    protected function process(array $data)
    {
        return $this->adapter->refundPayment($data['payment_id'], $data['amount']);
    }
}
