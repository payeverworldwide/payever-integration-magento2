<?php

namespace Payever\Payever\Gateway\Helper;

use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Payever\Payever\Gateway\Config\StatusConfig;

/**
 * Class SubjectReader
 */
class RetrieveResponseReader
{
    /**
     * Raw payever response
     *
     * @var array
     */
    private $response;

    /**
     * Converted statuses
     *
     * @var array
     */
    private $stateData;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var OrderFactor
     */
    private $orderFactory;

    /**
     * @var StatusConfig
     */
    private $statusConfig;

    /**
     * RetrieveResponseReader constructor.
     * @param QuoteFactory $quoteFactory
     * @param OrderFactory $orderFactory
     * @param StatusConfig $statusConfig
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        StatusConfig $statusConfig
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->orderFactory = $orderFactory;
        $this->statusConfig = $statusConfig;
    }

    /**
     * Read response data convert statuses
     *
     * @param $responseData
     * @return $this
     */
    public function read($responseData)
    {
        $this->response = $responseData;
        $this->stateData = $this->statusConfig->getStatusStateByPayeverStatus($this->getResponseStatus());
        return $this;
    }

    /**
     * Return response status
     *
     * @return mixed
     */
    public function getResponseStatus()
    {
        return $this->response['result']['status'];
    }

    /**
     * Return reserved order increment
     *
     * @return mixed
     */
    public function getReservedOrderId()
    {
        return $this->response['result']['reference'];
    }

    /**
     * Return payment method without prefix
     *
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->response['result']['payment_type'];
    }

    /**
     * Try load quote by order increment id
     *
     * @return mixed
     */
    public function getQuote()
    {
        return $this->quoteFactory->create()->load($this->getReservedOrderId(), 'reserved_order_id');
    }

    /**
     * Try load order by order increment id
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->orderFactory->create()->load($this->getReservedOrderId(), 'increment_id');
    }

    /**
     * Return response
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return payever transaction id
     *
     * @return string
     */
    public function getTxnId()
    {
        return $this->response['result']['id'];
    }

    /**
     * Return PAN ID
     *
     * @return null|string
     */
    public function getPanId()
    {
        if (!empty($this->response['result']['payment_details_array']['usageText'])) {
            return $this->response['result']['payment_details_array']['usageText'];
        }

        return null;
    }

    /**
     * Return payment details for blocks additional
     *
     * @return array
     */
    public function getPaymentDetails()
    {
        $ret = $this->response['result']['payment_details'];
        $ret['id'] = $this->getTxnId();
        return $ret;
    }

    /**
     * Return magento status for transaction
     *
     * @return string
     */
    public function getState()
    {
        return $this->stateData['state'];
    }

    /**
     * Return if transaction can be captured or not
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->stateData['can_capture'];
    }

    /**
     * Return if transaction can be canceled or not
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->stateData['canceled'];
    }
}
