<?php

namespace Payever\Payever\Observer;

use Magento\Framework\Event\ObserverInterface;
use Payever\Payever\Gateway\Config\Config;
use Magento\Framework\Event\Observer;

/**
 * Class PaymentMethodAvailableAfterObserver
 * @package Payever\Payever\Observer
 */
class PaymentMethodAvailableAfterObserver implements ObserverInterface
{

    /**
     * @var Config
     */
    private $config;

    /**
     * PaymentMethodAvailableAfterObserver constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $checkResult    = $observer->getEvent()->getResult();
        $quote          = $observer->getEvent()->getQuote();
        $methodInstance = $observer->getEvent()->getMethodInstance();

        if ($quote &&
            $checkResult->getData('is_available') &&
            $this->config->isPayeverMethod($methodInstance->getCode())) {
            $this->config->setMethodCode($methodInstance->getCode());

            $isAvailable = $this->validateMinMax($methodInstance, $quote) &&
                $this->validateCurrency($quote) && $this->validateHiddenMethods($methodInstance->getCode());
            $checkResult->setData('is_available', $isAvailable);
        }
    }

    /**
     * @param $method
     * @return bool
     */
    private function validateHiddenMethods($method)
    {
        $hiddenMethods = $this->config->getPayeverHiddenMethods();
        if (in_array($method,$hiddenMethods)) {
            return false;
        }
        return true;
    }

    /**
     * @param $quote
     * @return bool
     */
    private function validateCurrency($quote)
    {
        $currency = $quote->getQuoteCurrencyCode();

        $currencies = $this->config->getAllowedCurrencies($quote->getStoreId());
        if (in_array($currency, $currencies)) {
            return true;
        }
        return false;
    }

    /**
     * @param $methodInstance
     * @param $quote
     * @return bool
     */
    private function validateMinMax($methodInstance, $quote)
    {
        $minTotal = $this->config->getMinOrderTotal($quote->getStoreId());
        $maxTotal = $this->config->getMaxOrderTotal($quote->getStoreId());

        if ($this->config->isSantanderMethod($methodInstance->getCode())) {
            $total = $quote->getGrandTotal();
        } else {
            $total = $quote->getBaseGrandTotal();
        }

        if ((!empty($minTotal) && $total < $minTotal) || (!empty($maxTotal) && $total > $maxTotal)) {
            return false;
        }

        return true;
    }
}
