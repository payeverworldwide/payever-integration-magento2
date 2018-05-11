<?php

namespace Payever\Payever\Controller\Response;

use Magento\Framework\App\Action\Action;
use Payever\Payever\Model\Adapter\PayeverAdapter;
use Payever\Payever\Model\Helper\OrderPlace ;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Cancel
 * @package Payever\Payever\Controller\Response
 */
class Cancel extends Action
{

    const PAYMENT_PLACEHOLDER = '--PAYMENT-ID--';

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
        $paymentId = $this->getRequest()->getParam('payment_id');

        if ($paymentId && $paymentId != self::PAYMENT_PLACEHOLDER) {
            try {
                $response = $this->payeverAdapter->retrievePayment($paymentId);
                $this->orderPlace->execute($response, false);
                $this->messageManager->addNoticeMessage(__('Order was canceled'));
                $this->_redirect($this->_url->getUrl('checkout/cart'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addException($e, $e->getMessage());
            }
        }

        $this->_redirect($this->_url->getUrl('checkout', ['_fragment' => 'payment']));
    }
}
