<?php

namespace Payever\Payever\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class RetrieveResponse
 * @package Payever\Payever\Gateway\Response
 */
class RetrieveResponse implements HandlerInterface
{
    /**
     * Handles fraud messages
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $response = $response['object'];

        $payment->setAdditionalInformation($response->getPaymentDetails());
        $payment->setTransactionId($response->getTxnId());
        $payment->setIsTransactionClosed(false);
    }
}
