<?php

namespace Payever\Payever\Gateway\Http\Client;

/**
 * Class VoidTransaction
 * @package Payever\Payever\Gateway\Http\Client
 */
class VoidTransaction extends AbstractClient
{
    /**
     * @param array $data
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function process(array $data)
    {
        return $this->adapter->cancelPayment($data['payment_id']);
    }
}
