<?php

namespace Payever\Payever\Controller\Response;

use Magento\Framework\App\Action\Action;
use Payever\Payever\Model\Adapter\PayeverAdapter;
use Payever\Payever\Model\Helper\OrderPlace ;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Success
 * @package Payever\Payever\Controller\Response
 */
class Success extends Action
{

    /**
     * @var CommandPoolFactory
     */
    private $payeverAdapter;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderPlace
     */
    private $orderPlace;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Success constructor.
     * @param Context $context
     * @param OrderPlace $orderPlace
     * @param PayeverAdapter $payeverAdapter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        OrderPlace $orderPlace,
        PayeverAdapter $payeverAdapter,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderPlace = $orderPlace;
        $this->payeverAdapter = $payeverAdapter;
        $this->logger = $logger;
    }

    /**
     * Redirect to success or checkout page
     */
    public function execute()
    {
        $request = $this->getRequest();
        $paymentId = $request->getParam('payment_id');

        try {
            if (!$paymentId) {
                throw new \InvalidArgumentException('Please specify payment');
            }
            $response = $this->payeverAdapter->retrievePayment($paymentId);
            $this->orderPlace->execute($response);

            $query = [];
            if ($request->getParam('is_pending')) {
                $query = ['is_pending' => 'true'];
            }

            $this->_redirect(
                $this->_url->getUrl('checkout/onepage/success', ['_secure' => true, '_query' => $query])
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addException($e, $e->getMessage());
            $this->_redirect($this->_url->getUrl('checkout', ['_fragment' => 'payment']));
        }
    }
}
