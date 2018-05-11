<?php
namespace Payever\Payever\Model;

use Payever\Payever\Gateway\Command\CreatePaymentCommandFactory;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Payever\Payever\Model\Data\CreatePaymentResponse;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Checkout\Model\Type\Onepage;
use Payever\Payever\Api\CreatePaymentManagementInterface;

/**
 * Class CreatePaymentManagement
 * @package Payever\Payever\Model
 */
class CreatePaymentManagement implements CreatePaymentManagementInterface
{

    /**
     * @var BillingAddressManagementInterface
     */
    private $billingAddressManagement;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

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
     * CreatePaymentManagement constructor.
     * @param CreatePaymentCommandFactory $command
     * @param BillingAddressManagementInterface $billingAddressManagement
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CreatePaymentResponse $createPaymentResponse
     */
    public function __construct(
        CreatePaymentCommandFactory $command,
        BillingAddressManagementInterface $billingAddressManagement,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentInformationManagementInterface $paymentInformationManagement,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        CartRepositoryInterface $cartRepository,
        CreatePaymentResponse $createPaymentResponse
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->cartRepository = $cartRepository;
        $this->command = $command;
        $this->createPaymentResponse = $createPaymentResponse;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
        try {
            $quote = $this->cartRepository->getActive($cartId);

            $quote->setCheckoutMethod(Onepage::METHOD_CUSTOMER)->save();

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
     * {@inheritDoc}
     */
    public function savePaymentInformation(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {

        if ($billingAddress) {
            $this->billingAddressManagement->assign($cartId, $billingAddress);
        }
        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        return true;
    }
}
