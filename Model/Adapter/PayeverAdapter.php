<?php

namespace Payever\Payever\Model\Adapter;

use Payever\Payever\Gateway\Config\Config;
use Payever\Payever\Gateway\Helper\RetrieveResponseReaderFactory;
use Magento\Framework\Registry;
use Payever\Payever\Model\Api\PayeverApi;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class PayeverAdapter
 * @codeCoverageIgnore
 */
class PayeverAdapter
{

    /**
     * Payever registry prefix method
     */
    const REGISTRY_PREFIX = 'payever_';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var PayeverApi
     */
    private $payeverApi;

    /**
     * @var RetrieveResponseReaderFactory
     */
    private $retrieveResponseReaderFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * PayeverAdapter constructor.
     *
     * @param Config $config
     * @param Encryptor $encryptor
     * @param PayeverApi $payeverApi
     * @param RetrieveResponseReaderFactory $retrieveResponseReaderFactory
     * @param Registry $registry
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Config $config,
        Encryptor $encryptor,
        PayeverApi $payeverApi,
        RetrieveResponseReaderFactory $retrieveResponseReaderFactory,
        Registry $registry
    ) {
    
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->payeverApi = $payeverApi;
        $this->retrieveResponseReaderFactory = $retrieveResponseReaderFactory;
        $this->registry = $registry;
        $this->initCredentials();
    }

    /**
     * Init payever API class.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initCredentials()
    {
        $this->payeverApi->setUpCredentials(
            $this->encryptor->decrypt($this->config->getClientId()),
            $this->encryptor->decrypt($this->config->getClientSecret()),
            $this->config->getEnvironment(),
            $this->config->getCustomApiUrl()
        );
    }

    /**
     * @param $encodedClientId
     * @param $encodedClientSecret
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setUpCredentials($encodedClientId, $encodedClientSecret)
    {
        $this->payeverApi->setUpCredentials(
            $this->encryptor->decrypt($encodedClientId),
            $this->encryptor->decrypt($encodedClientSecret),
            $this->config->getEnvironment(),
            $this->config->getCustomApiUrl()
        );

        return $this;
    }

    /**
     * Send sipping goods request (capture)
     *
     * @param $paymentId
     * @param $customerId
     * @param $invoiceId
     * @param $date
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function shippingGoodsPayment($paymentId, $customerId, $invoiceId, $date)
    {
        if ($this->isAllowTransaction($paymentId, 'shipping_goods', false)) {
            $data = [
                'payment_id' => $paymentId,
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'invoice_date' => $date,
            ];
            return $this->payeverApi->shippingGoodsPayment($data);
        }

        return false;
    }

    /**
     * Refund payment request
     *
     * @param $paymentId
     * @param $amount
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refundPayment($paymentId, $amount)
    {
        return $this->payeverApi->refundPayment($paymentId, $amount);
    }

    /**
     * Cancel payment request
     *
     * @param $paymentId
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelPayment($paymentId)
    {
        if ($this->isAllowTransaction($paymentId, 'cancel')) {
            return $this->payeverApi->cancelPayment($paymentId);
        }
        return false;
    }

    /**
     * Create payment request
     *
     * @param $data
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPayment($data)
    {
        return $this->payeverApi->createPayment($data);
    }

    /**
     * Get transactions request
     *
     * @param $paymentId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTransaction($paymentId)
    {
        return $this->payeverApi->getTransaction($paymentId);
    }

    /**
     * @param        $paymentId
     * @param string $typeTransaction
     * @param bool   $throwException
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function isAllowTransaction(
        $paymentId,
        $typeTransaction = 'cancel',
        $throwException = true
    ) {
        $transaction = $this->getTransaction($paymentId);

        if (!empty($transaction['actions']) && is_array($transaction['actions'])) {
            foreach ($transaction['actions'] as $action) {
                if ($action['action'] == $typeTransaction) {
                    return $action['enabled'];
                }
            }
        }

        if ($throwException) {
            throw new LocalizedException(
                __(
                    sprintf(
                        'Action "%s" is disallowed for payment id "%s"',
                        $typeTransaction,
                        $paymentId
                    )
                )
            );
        } else {
            return false;
        }
    }

    /**
     * Get details about payment
     *
     * @param $paymentId
     * @param bool $isNeedRegistry
     * @return $this|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrievePayment($paymentId, $isNeedRegistry = true)
    {
        if ($isNeedRegistry) {
            $key = self::REGISTRY_PREFIX . $paymentId;
            if (!$response = $this->registry->registry($key)) {
                $response = $this->payeverApi->retrievePayment($paymentId);
                $response = $this->retrieveResponseReaderFactory->create()->read($response);
                $this->registry->register($key, $response);
            }
        } else {
            $response = $this->payeverApi->retrievePayment($paymentId);
            $response = $this->retrieveResponseReaderFactory->create()->read($response);
        }

        return $response;
    }

    /**
     * Get payment settings for synchronization
     *
     * @param $slug
     * @param string $currency
     * @param string $lang
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentSettings($slug, $currency = '', $lang = '')
    {
        return $this->payeverApi->getListPaymentOptions($slug, $this->config->getModuleChannel(), $currency, $lang);
    }
}
