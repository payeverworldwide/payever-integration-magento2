<?php

namespace Payever\Payever\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payever\Payever\Gateway\Helper\SubjectReader;

/**
 * Class PaymentRequest
 * @package Payever\Payever\Gateway\Request
 */
class PaymentRequest implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * PaymentRequest constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
    
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $txnId = $payment->getLastTransId() ? $payment->getLastTransId(): $payment->getAdditionalInformation('txn_id');
        return [
            'payment_id' => $txnId
        ];
    }
}
