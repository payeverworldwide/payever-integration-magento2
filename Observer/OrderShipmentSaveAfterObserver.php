<?php

namespace Payever\Payever\Observer;

use Magento\Framework\Event\ObserverInterface;
use Payever\Payever\Gateway\Config\Config;
use Payever\Payever\Model\Adapter\PayeverAdapter;
use Magento\Framework\Event\Observer;

class OrderShipmentSaveAfterObserver implements ObserverInterface
{

    /**
     * @var PayeverAdapter
     */
    private $payeverAdapter;

    /**
     * @var Config
     */
    private $config;

    /**
     * OrderShipmentSaveAfterObserver constructor.
     * @param PayeverAdapter $payeverAdapter
     * @param Config $config
     */
    public function __construct(
        PayeverAdapter $payeverAdapter,
        Config $config
    ) {
    
        $this->payeverAdapter = $payeverAdapter;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $payment = $order->getPayment();

        if ($payment->getLastTransId() &&
            $this->config->isPayeverMethod($order->getPayment()->getMethod()) &&
            !$order->canShip()) {
            $invoice = $order->getInvoiceCollection()->getFirstItem();

            $this->payeverAdapter->shippingGoodsPayment(
                $payment->getLastTransId(),
                $order->getCustomerId(),
                $invoice->getId(),
                $invoice->getCreatedAt()
            );
        }
    }
}
