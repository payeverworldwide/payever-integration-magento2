<?php

namespace Payever\Payever\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

class EmailOrderSetTemplateVarsBeforeObserver implements ObserverInterface
{
    /** @var QuoteFactory $quoteFactory */
    protected $quoteFactory;

    public function __construct(QuoteFactory $quoteFactory)
    {
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $transport */
        $transport = $observer->getTransport();

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create()->load($transport->getOrder()->getQuoteId());

        $transport['payeverPanId'] = $quote->getPayment()->getAdditionalInformation('pan_id');
    }
}
