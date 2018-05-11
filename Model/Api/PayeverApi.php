<?php
namespace Payever\Payever\Model\Api;

use Magento\Payment\Model\Method\Logger;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

class PayeverApi extends DataObject
{

    const ENVIRONMENT_PRODUCTION = 'mein';
    const ENVIRONMENT_SANDBOX = 'sandbox';

    const API_URL_LIVE = 'https://mein.payever.de/';
    const API_URL_SANDBOX = 'https://sandbox.payever.de/';

    const API_URL_AUTH = 'oauth/v2/token';
    const API_URL_CREATE_PAYMENT = 'api/payment';

    const API_URL_RETRIEVE_PAYMENT = 'api/payment/%s';
    const API_URL_REFUND_PAYMENT = 'api/payment/refund/%s';
    const API_URL_CANCEL_PAYMENT = 'api/payment/cancel/%s';
    const API_URL_SHIPPING_GOODS_PAYMENT = 'api/payment/shipping-goods/%s';
    const API_URL_TRANSACTION = 'api/rest/v1/transactions/%s';
    const API_URL_LIST_PAYMENT_OPTIONS = 'api/shop/%s/payment-options/%s';
    const API_GRAND_TYPE = 'http://www.payever.de/api/payment';
    const API_CREATE_PAYMENT = 'API_CREATE_PAYMENT';
    const API_PAYMENT_INFO = 'API_PAYMENT_INFO';
    const API_PAYMENT_ACTIONS = 'API_PAYMENT_ACTIONS';
    const ERROR_WRONG_CLIENT_DATA = 'API ERROR: Wrong client id or client secret';

    private $clientId;
    private $clientSecret;
    private $lastAuthenticationResponse;
    private $mode;
    private $config = [
        'timeout' => 60,
        'verifypeer' => false,
        'verifyhost' => false,
        'header' => 0
    ];

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $customApiUrl;

    /**
     * PayeverApi constructor.
     * @param CurlFactory $curlFactory
     * @param Data $jsonHelper
     * @param Logger $logger
     */
    public function __construct(
        CurlFactory $curlFactory,
        Data $jsonHelper,
        Logger $logger
    ) {

        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @param $mode
     * @throws LocalizedException
     */
    public function setUpCredentials($clientId, $clientSecret, $mode = self::SANDBOX_MODE, $customApiUrl = null)
    {
        if (!empty($clientId) && !empty($clientSecret)) {
            $this->clientId = $clientId;
            $this->clientSecret = $clientSecret;
            $this->mode = $mode;
            $this->customApiUrl = $customApiUrl;
        } else {
            throw new LocalizedException(
                __(self::ERROR_WRONG_CLIENT_DATA)
            );
        }
    }

    public function getBaseURL()
    {
        switch ($this->mode) {
            case self::ENVIRONMENT_PRODUCTION:
                $url = self::API_URL_LIVE;
                break;
            case self::ENVIRONMENT_SANDBOX:
                $url = $this->customApiUrl ? $this->customApiUrl : self::API_URL_SANDBOX;
                break;
            default:
                $url = self::API_URL_SANDBOX;
                break;
        }
        return $url;
    }

    public function getAuthToken()
    {
        return $this->lastAuthenticationResponse['access_token'];
    }

    public function getListPaymentOptionsURL($slug, $channel, $currency = '', $lang = '')
    {
        if (!empty($lang)) {
            $params['_locale'] = $lang;
        }
        if (!empty($currency)) {
            $params['_currency'] = $currency;
        }

        return sprintf(self::API_URL_LIST_PAYMENT_OPTIONS, $slug, $channel)
            . (empty($params) ? '' : '?' . http_build_query($params));
    }

    /**
     * @param $method
     * @param $url
     * @param array $headers
     * @param string $body
     * @return mixed
     * @throws LocalizedException
     */
    public function execCurl($method, $url, $headers = [], $body = '')
    {
        $http = $this->curlFactory->create();

        $url = $this->getBaseURL() . $url;

        $http->setConfig($this->config);
        $http->write(
            $method,
            $url,
            '1.1',
            $headers,
            $body
        );
        $this->logger->debug(
            [
                '$method' => $method,
                '$url' => $url,
                '$headers' => $headers,
                '$body' => $body
            ]
        );

        $response = $http->read();

        $this->logger->debug(
            [
                '$response' => $response
            ]
        );

        if ($http->getErrno()) {
            $errorNumber = $http->getErrno();
            $error = $http->getError();
            $http->close();

            throw new LocalizedException(
                __('CURL connection error #%s: %s', $errorNumber, $error)
            );
        }

        $http->close();

        $responseJSON = $this->jsonHelper->jsonDecode($response);

        if (!empty($responseJSON['error'])) {
            throw new LocalizedException(
                __($responseJSON['error'] . ' : ' . $responseJSON['error_description'])
            );
        }

        return $responseJSON;
    }

    /**
     * @param string $scope
     * @return mixed
     * @throws LocalizedException
     */
    public function authenticationRequest($scope = self::API_CREATE_PAYMENT)
    {
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => self::API_GRAND_TYPE,
            'scope' => $scope
        ];

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::POST,
            self::API_URL_AUTH,
            [],
            $postData
        );

        $this->lastAuthenticationResponse = $responseJSON;

        return $this->lastAuthenticationResponse;
    }

    /**
     * @param $data
     * @return mixed
     * @throws LocalizedException
     */
    public function createPayment($data)
    {
        $this->authenticationRequest();

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::POST,
            self::API_URL_CREATE_PAYMENT,
            ['Authorization: Bearer ' . $this->getAuthToken()],
            $data
        );

        return $responseJSON;
    }

    /**
     * @param $paymentId
     * @return mixed
     * @throws LocalizedException
     */
    public function retrievePayment($paymentId)
    {
        $this->authenticationRequest();

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::GET,
            sprintf(self::API_URL_RETRIEVE_PAYMENT, $paymentId),
            ['Authorization: Bearer ' . $this->getAuthToken()]
        );

        return $responseJSON;
    }

    /**
     * @param $paymentId
     * @return mixed
     * @throws LocalizedException
     */
    public function cancelPayment($paymentId)
    {
        $this->authenticationRequest(self::API_PAYMENT_ACTIONS);

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::POST,
            sprintf(self::API_URL_CANCEL_PAYMENT, $paymentId),
            ['Authorization: Bearer ' . $this->getAuthToken()]
        );

        return $responseJSON;
    }

    /**
     * @param $data
     * @return mixed
     * @throws LocalizedException
     */
    public function shippingGoodsPayment($data)
    {
        $this->authenticationRequest(self::API_PAYMENT_ACTIONS);

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::POST,
            sprintf(self::API_URL_SHIPPING_GOODS_PAYMENT, $data['payment_id']),
            ['Authorization: Bearer ' . $this->getAuthToken()],
            [
                'customer_id' => $data['customer_id'],
                'invoice_id' => $data['invoice_id'],
                'invoice_date' => $data['invoice_date'],
            ]
        );

        return $responseJSON;
    }

    /**
     * @param $paymentId
     * @return mixed
     * @throws LocalizedException
     */
    public function getTransaction($paymentId)
    {
        $this->authenticationRequest(self::API_PAYMENT_ACTIONS);

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::GET,
            sprintf(self::API_URL_TRANSACTION, $paymentId),
            ['Authorization: Bearer ' . $this->getAuthToken()]
        );

        return $responseJSON;
    }

    /**
     * @param $paymentId
     * @param $amount
     * @return mixed
     * @throws LocalizedException
     */
    public function refundPayment($paymentId, $amount)
    {
        $this->authenticationRequest();

        $responseJSON = $this->execCurl(
            \Zend_Http_Client::POST,
            sprintf(self::API_URL_REFUND_PAYMENT, $paymentId),
            ['Authorization: Bearer ' . $this->getAuthToken()],
            [
                'amount' => $amount
            ]
        );

        return $responseJSON;
    }

    /**
     * @param $slug
     * @param $channel
     * @param string $currency
     * @param string $lang
     * @return mixed
     * @throws LocalizedException
     */
    public function getListPaymentOptions($slug, $channel, $currency = '', $lang = '')
    {
        $responseJSON = $this->execCurl(
            \Zend_Http_Client::GET,
            $this->getListPaymentOptionsURL($slug, $channel, $currency, $lang)
        );

        return $responseJSON;
    }
}
