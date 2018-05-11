<?php

namespace Payever\Payever\Api;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Interface for managing guest payment information
 * @api
 */
interface CreatePaymentManagementInterface
{
    /**
     * Set payment information and save quote for a specified cart.
     *
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return \Payever\Payever\Api\Data\CreatePaymentResponseInterface Item
     */
    public function createPayment(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    );
}
