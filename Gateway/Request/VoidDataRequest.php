<?php
namespace Payever\Payever\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payever\Payever\Gateway\Helper\SubjectReader;

/**
 * Class VoidDataRequest
 * @package Payever\Payever\Gateway\Request
 */
class VoidDataRequest implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * VoidDataRequest constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        return [
            'payment_id' =>  $payment->getParentTransactionId()
                ?: $payment->getLastTransId()
        ];
    }
}
