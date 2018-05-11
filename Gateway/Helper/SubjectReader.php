<?php
namespace Payever\Payever\Gateway\Helper;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Gateway\Helper;

/**
 * Class SubjectReader
 */
class SubjectReader
{
    /**
     * Reads payment_id value from subject
     *
     * @param array $subject
     * @return object
     */
    public function readPaymentId(array $subject)
    {
        if (empty($subject['payment_id'])) {
            throw new \InvalidArgumentException('Please specify payment id');
        }

        return $subject['payment_id'];
    }

    /**
     * Reads quote
     *
     * @param array $subject
     * @return mixed
     */
    public function readQuote(array $subject)
    {
        if (!isset($subject['quote']) || !$subject['quote'] instanceof CartInterface) {
            throw new \InvalidArgumentException('Please specify quote');
        }

        return $subject['quote'];
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface
     */
    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }
}
