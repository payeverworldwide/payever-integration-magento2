<?php

namespace Payever\Payever\Gateway\Http\Client;

/**
 * Class Authorization
 * @package Payever\Payever\Gateway\Http\Client
 */
class Authorization extends AbstractClient
{
    /**
     * @param array $data
     *
     * @return $this|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function process(array $data)
    {
        return $this->adapter->retrievePayment($data['payment_id']);
    }
}
