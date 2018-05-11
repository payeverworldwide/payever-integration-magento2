<?php
namespace Payever\Payever\Gateway\Config;

use Magento\Sales\Model\Order;

/**
 * Class Config
 */
class StatusConfig
{

    /**
     * Payever statuses
     */
    const PAYEVER_STATUS_NEW = 'STATUS_NEW';
    const PAYEVER_STATUS_IN_PROCESS = 'STATUS_IN_PROCESS';
    const PAYEVER_STATUS_ACCEPTED = 'STATUS_ACCEPTED';
    const PAYEVER_STATUS_FAILED = 'STATUS_FAILED';
    const PAYEVER_STATUS_DECLINED = 'STATUS_DECLINED';
    const PAYEVER_STATUS_REFUNDED = 'STATUS_REFUNDED';
    const PAYEVER_STATUS_PAID = 'STATUS_PAID';
    const PAYEVER_STATUS_CANCELLED = 'STATUS_CANCELLED';
    const PAYEVER_STATUS_IN_COLLECTION = 'STATUS_IN_COLLECTION';

    /**
     * Convert payever status to Magento
     *
     * @param $responseStatus
     * @return array
     */
    public function getStatusStateByPayeverStatus($responseStatus)
    {
        $canCapture = false;

        switch ($responseStatus) {
            case self::PAYEVER_STATUS_PAID:
                $canCapture = true;
                $state = Order::STATE_PROCESSING;
                break;
            case self::PAYEVER_STATUS_ACCEPTED:
                $canCapture = true;
                $state = Order::STATE_PROCESSING;
                break;
            case self::PAYEVER_STATUS_IN_PROCESS:
                $state = Order::STATE_PAYMENT_REVIEW;
                break;
            case self::PAYEVER_STATUS_DECLINED:
                $state = Order::STATE_CANCELED;
                break;
            case self::PAYEVER_STATUS_REFUNDED:
                $state = Order::STATE_CANCELED;
                break;
            case self::PAYEVER_STATUS_FAILED:
                $state = Order::STATE_CANCELED;
                break;
            case self::PAYEVER_STATUS_CANCELLED:
                $state = Order::STATE_CANCELED;
                break;
            case self::PAYEVER_STATUS_NEW:
                $state = Order::STATE_PENDING_PAYMENT;
                break;
            default:
                $state = Order::STATE_PAYMENT_REVIEW;
                break;
        }

        return [
            'can_capture' => $canCapture,
            'state'   => $state,
            'canceled' => $state == Order::STATE_CANCELED
        ];
    }
}
