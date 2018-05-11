<?php

namespace Payever\Payever\Model;

use Magento\Quote\Api\GuestBillingAddressManagementInterface;
use Magento\Quote\Api\GuestPaymentMethodManagementInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Payever\Payever\Gateway\Command\CreatePaymentCommandFactory;
use Payever\Payever\Model\Data\CreatePaymentResponse;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Payever\Payever\Api\GuestCreatePaymentManagementInterface;
use Magento\Checkout\Model\Type\Onepage;

class GuestCreatePaymentManagement implements GuestCreatePaymentManagementInterface
{

    /**
     * @var GuestBillingAddressManagementInterface
     */
    private $billingAddressManagement;

    /**
     * @var GuestPaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CreatePaymentCommandFactory
     */
    private $command;

    /**
     * @var CreatePaymentResponse
     */
    private $createPaymentResponse;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * GuestCreatePaymentManagement constructor.
     * @param GuestBillingAddressManagementInterface $billingAddressManagement
     * @param GuestPaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CreatePaymentCommandFactory $command
     * @param CreatePaymentResponse $createPaymentResponse
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     */
    public function __construct(
        GuestBillingAddressManagementInterface $billingAddressManagement,
        GuestPaymentMethodManagementInterface $paymentMethodManagement,
        PaymentInformationManagementInterface $paymentInformationManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository,
        CreatePaymentCommandFactory $command,
        CreatePaymentResponse $createPaymentResponse,
        PaymentDataObjectFactory $paymentDataObjectFactory
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->command = $command;
        $this->createPaymentResponse = $createPaymentResponse;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return \Payever\Payever\Api\Data\CreatePaymentResponseInterface|CreatePaymentResponse
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function guestCreatePayment(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $this->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);

        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

            $quote->setCheckoutMethod(Onepage::METHOD_GUEST)->save();
            $paymentData = $this->paymentDataObjectFactory->create($quote->getPayment());

            $ret = $this->command->create()->execute([
                'quote' => $quote,
                'payment' => $paymentData
            ]);

            $this->createPaymentResponse
                ->setUrl($ret->get()['url'])
                ->setError('');
        } catch (\Exception $e) {
            $this->createPaymentResponse
                ->setUrl('')
                ->setError($e->getMessage());
        }

        return $this->createPaymentResponse;
    }

    /**
     * @param $cartId
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if ($billingAddress) {
            $billingAddress->setEmail($email);
            $this->billingAddressManagement->assign($cartId, $billingAddress);
        } else {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $this->cartRepository->getActive($quoteIdMask->getQuoteId())->getBillingAddress()->setEmail($email);
        }

        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        return true;
    }
}
