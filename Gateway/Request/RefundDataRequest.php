<?php
namespace Payever\Payever\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payever\Payever\Gateway\Helper\SubjectReader;
use Payever\Payever\Helper\Formatter;

/**
 * Class RefundDataRequest
 * @package Payever\Payever\Gateway\Request
 */
class RefundDataRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * RefundDataRequest constructor.
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

        $amount = null;
        try {
            $amount = $this->formatPrice($this->subjectReader->readAmount($buildSubject));
        } catch (\InvalidArgumentException $e) {
        }
        $txnId = $payment->getLastTransId();
        return [
            'payment_id' => $txnId,
            'amount' => $amount
        ];
    }
}
