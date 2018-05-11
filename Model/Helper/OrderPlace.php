<?php

namespace Payever\Payever\Model\Helper;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Payever\Payever\Gateway\Config\Config;
use Magento\Sales\Model\Service\InvoiceServiceFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerFactory;
use Payever\Payever\Gateway\Helper\RetrieveResponseReader;
use Payever\Payever\Model\Locker;
use Magento\Sales\Model\Order\Invoice;

/**
 * Class OrderPlace
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPlace
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var InvoiceServiceFactory
     */
    private $invoiceServiceFactory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var
     */
    private $messages;

    /**
     * @var ManagerFactory
     */
    private $eventManagerFactory;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var OrderSender
     */
    private $orderSender;
    
    /**
     * OrderPlace constructor.
     * @param Logger $logger
     * @param CartManagementInterface $cartManagement
     * @param OrderFactory $orderFactory
     * @param Config $config
     * @param InvoiceServiceFactory $invoiceServiceFactory
     * @param TransactionFactory $transactionFactory
     * @param Session $checkoutSession
     * @param ManagerFactory $eventManagerFactory
     * @param Locker $locker
     * @param OrderSender $orderSender
     */
    public function __construct(
        Logger $logger,
        CartManagementInterface $cartManagement,
        OrderFactory $orderFactory,
        Config $config,
        InvoiceServiceFactory $invoiceServiceFactory,
        TransactionFactory $transactionFactory,
        Session $checkoutSession,
        ManagerFactory $eventManagerFactory,
        Locker $locker,
        OrderSender $orderSender
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->config = $config;
        $this->invoiceServiceFactory = $invoiceServiceFactory;
        $this->transactionFactory = $transactionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->eventManagerFactory = $eventManagerFactory;
        $this->locker = $locker;
        $this->orderSender = $orderSender;
    }

    /**
     * @param RetrieveResponseReader $response
     * @return mixed
     * @throws \Exception
     */
    public function execute(RetrieveResponseReader $response, $updateSession = true)
    {
        $this->addMessage(sprintf(
            'Response was retrieved, payment_id = %s',
            $response->getTxnId()
        ));

        try {
            $this->locker->waitForUnlock($response->getTxnId());
            $this->locker->lockAndBlock($response->getTxnId());

            $order = $response->getOrder();
            $quote = $response->getQuote();

            if (!$quote->getId() && !$order->getId()) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Quote was not found autoincrement %s',
                        $response->getReservedOrderId()
                    )
                );
            }

            if (!$order->getId() && $quote->getId()) {
                $quote->collectTotals();

                $quote
                    ->getPayment()
                    ->setAdditionalInformation('txn_id', $response->getTxnId())
                    ->setAdditionalInformation('pan_id', $response->getPanId())
                    ->save();

                $orderId = $this->cartManagement->placeOrder($quote->getId());
                $order = $this->orderFactory->create()->load($orderId);

                $this->addMessage(sprintf(
                    'Order was created, order_id = %d',
                    $orderId
                ));
            }

            if (!$order->getId()) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Order was not found autoincrement %s',
                        $response->getReservedOrderId()
                    )
                );
            }

            if ($response->canCapture()) {
                $this->addMessage(sprintf(
                    'Order can be captured, order_id = %d',
                    $order->getId()
                ));

                $this->captureOrder($order, $response);
                $this->sendOrderConfirmation($order);
            } elseif ($response->isCanceled()) {
                $this->cancelOrder($order);
                $updateSession = false;
                $this->addMessage(sprintf(
                    'Order was canceled, order_id = %d',
                    $order->getId()
                ));
            }

            $this->updateOrderState($order, $response->getState(), $response->getResponseStatus());
            if ($updateSession) {
                $this->updateCheckoutSession($order, $quote);
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->logger->debug($this->getMessages());
            $this->locker->unlock($response->getTxnId());
        }
    }

    /**
     * @param Order $order
     * @param $orderState
     * @param $responseStatus
     * @throws \Exception
     */
    private function updateOrderState(Order $order, $orderState, $responseStatus)
    {
        $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);

        if (($order->getState() != $orderState) || ($order->getStatus() != $orderStatus)) {
            $order->setState($orderState)->setStatus($orderStatus);

            $isCustomerNotified = $order->getCustomerNoteNotify();
            $order->addStatusToHistory($orderStatus, '', $isCustomerNotified);

            $this->eventManagerFactory->create()->dispatch(
                'payever_change_order_status',
                [
                    'order' => $order,
                    'state' => $orderState,
                    'status' => $orderStatus,
                    'response_status' => $responseStatus
                ]
            );
            $order->save();

            $this->addMessage(sprintf(
                'Status was changed, order_id = %d; new state = %s; status = %s; response_status = %s ',
                $order->getId(),
                $orderState,
                $orderStatus,
                $responseStatus
            ));
        }
    }

    /**
     * @param Order $order
     * @param $quote
     */
    private function updateCheckoutSession(Order $order, $quote)
    {
        if ($quote) {
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        }
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
    }

    /**
     * @param $order
     * @param $response
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function captureOrder(Order $order, $response)
    {
        $paymentMethod = $this->config->addMethodPrefix($response->getPaymentMethod());
        $canAutoCapture = $this->config->getMethodValue('auto_capture', $paymentMethod);

        if ($canAutoCapture && $order->canInvoice()) {
            $invoice = $this->invoiceServiceFactory->create()->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();

            $this->transactionFactory->create()->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            )->save();

            $this->addMessage(sprintf(
                'Invoice was created, order_id = %d , invoice_id = %d ',
                $order->getId(),
                $invoice->getId()
            ));
        }
    }

    /**
     * Send order confirmation email
     *
     * @param Order $order
     */
    private function sendOrderConfirmation($order)
    {
        if ($order->getEmailSent()) {
            return;
        }

        try {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__('Order confirmation email sent to customer'))
                ->setIsCustomerNotified(true)
                ->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment(__('Failed to send order confirmation email: %s', $e->getMessage()))
                ->setIsCustomerNotified(false)
                ->save();
        }
    }

    /**
     * @param Order $order
     * @throws \Exception
     */
    private function cancelOrder(Order $order)
    {
        $order->cancel()->save();
    }

    /**
     * @param $message
     */
    private function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
