<?php

namespace Payever\Payever\Controller\Response;

use Magento\Framework\App\Action\Action;
use Payever\Payever\Model\Adapter\PayeverAdapter;
use Payever\Payever\Model\Helper\OrderPlace ;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Notice
 * @package Payever\Payever\Controller\Response
 */
class Notice extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var OrderPlace
     */
    private $orderPlace;

    /**
     * @var PayeverAdapter
     */
    private $payeverAdapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param OrderPlace $orderPlace
     * @param JsonFactory $jsonFactory
     * @param PayeverAdapter $payeverAdapter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        OrderPlace $orderPlace,
        JsonFactory $jsonFactory,
        PayeverAdapter $payeverAdapter,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->orderPlace = $orderPlace;
        $this->payeverAdapter = $payeverAdapter;
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $paymentId = $this->getRequest()->getParam('payment_id');
        $jsonResult = $this->jsonFactory->create();

        try {
            if (!$paymentId) {
                throw new \InvalidArgumentException('Please specify payment');
            }
            $response = $this->payeverAdapter->retrievePayment($paymentId);
            $this->orderPlace->execute($response);

            $jsonResult
                ->setHttpResponseCode(200)
                ->setData([
                    'result' => 'success',
                    'message' => $this->orderPlace->getMessages()
                ]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $jsonResult
                ->setHttpResponseCode(400)
                ->setData([
                    'result' => 'error',
                    'message' => $e->getMessage()
                ]);
        }

        return $jsonResult;
    }
}
