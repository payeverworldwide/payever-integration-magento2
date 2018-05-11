<?php

namespace Payever\Payever\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class CreatePaymentHandler
 * @package Payever\Payever\Gateway\Response
 */
class CreatePaymentHandler implements HandlerInterface
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

        $payment->setAdditionalInformation([
            'call_id'    => $response['object']['call']['id'],
            'created_at' => $response['object']['call']['created_at'],
            'order_id' => $response['object']['call']['order_id']
        ])->save();
    }
}
