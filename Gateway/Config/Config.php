<?php

namespace Payever\Payever\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Unserialize\Unserialize;
use Magento\Customer\Model\Session;

/**
 * Class Config
 */
class Config implements ConfigInterface
{
    /**
     * Payever module channel
     */
    const MODULE_CHANNEL = 'magento';

    /**
     * Common settings path
     */
    const DEFAULT_METHOD = 'payever';

    /**
     * Prefix for payever methods
     */
    const METHOD_PREFIX = 'payever_';

    /**
     * Prefix for Santander methods
     */
    const SANTANDER_PREFIX = 'santander';

    /**
     * Default config path for payment config
     */
    const DEFAULT_PATH_PATTERN = 'payment/%s/%s';

    /**
     * Config value key for environment
     */
    const KEY_ENVIRONMENT = 'environment';

    /**
     * Config value key for payever client id
     */
    const KEY_CUSTOM_API_URL = 'custom_api_url';

    /**
     * Config value key for payever client id
     */
    const KEY_CLIENT_ID = 'client_id';

    /**
     * Config value key for payever client secret
     */
    const KEY_CLIENT_SECRET = 'client_secret';

    /**
     * Config value key for payever client secret
     */
    const KEY_LOCALE = 'locale';

    /**
     * Config value key for payever slug
     */
    const KEY_SLUG = 'slug';

    /**
     * Config value key for options
     */
    const KEY_OPTIONS = 'options';

    /**
     * Config value key for min order total
     */
    const KEY_MIN_ORDER_TOTAL = 'min_payever_order_total';

    /**
     * Config value key for max order total
     */
    const KEY_MAX_ORDER_TOTAL = 'max_payever_order_total';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string|null
     */
    private $methodCode;

    /**
     * @var string|null
     */
    private $pathPattern;

    /**
     * @var Unserialize
     */
    private $unserialize;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Unserialize $unserialize
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Unserialize $unserialize,
        Session $customerSession,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->methodCode = $methodCode == null ? self::DEFAULT_METHOD : $methodCode;
        $this->pathPattern = $pathPattern;
        $this->unserialize = $unserialize;
        $this->customerSession = $customerSession;
    }

    /**
     * Set method code
     *
     * @param string $methodCode
     * @return $this
     */
    public function setMethodCode($methodCode)
    {
        $this->methodCode = $methodCode;
        return $this;
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * @param string $pathPattern
     * @return $this|void
     */
    public function setPathPattern($pathPattern)
    {
        $this->pathPattern = $pathPattern;
        return $this;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        if ($this->methodCode === null || $this->pathPattern === null) {
            return null;
        }

        return $this->scopeConfig->getValue(
            sprintf($this->pathPattern, $this->methodCode, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param string $methodCode
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getMethodValue($field, $methodCode = self::DEFAULT_METHOD, $storeId = null)
    {
        if ($this->pathPattern === null) {
            return null;
        }

        return $this->scopeConfig->getValue(
            sprintf($this->pathPattern, $methodCode, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if payment method is payever method
     *
     * @param $method
     * @return bool
     */
    public function isPayeverMethod($method)
    {
        return strpos($method, self::METHOD_PREFIX) !== false;
    }

    /**
     * Check if payment method is santander method
     *
     * @param $method
     * @return bool
     */
    public function isSantanderMethod($method)
    {
        return strpos($method, self::SANTANDER_PREFIX) !== false;
    }

    /**
     * Remove prefix from method code
     *
     * @param $method
     * @return string
     */
    public function removeMethodPrefix($method)
    {
        return trim(str_replace(self::METHOD_PREFIX, "", $method));
    }

    /**
     * Add prefix to method code
     *
     * @param $method
     * @return string
     */
    public function addMethodPrefix($method)
    {
        return self::METHOD_PREFIX . $method;
    }

    /**
     * Return API channel
     *
     * @return string
     */
    public function getModuleChannel()
    {
        return self::MODULE_CHANNEL;
    }

    /**
     * Return environment config value
     *
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->getMethodValue(self::KEY_ENVIRONMENT);
    }


    /**
     * Return environment config value
     *
     * @return mixed
     */
    public function getCustomApiUrl()
    {
        return $this->getMethodValue(self::KEY_CUSTOM_API_URL);
    }


    /**
     * Return client id config value
     *
     * @return mixed
     */
    public function getClientId()
    {
        return $this->getMethodValue(self::KEY_CLIENT_ID);
    }

    /**
     * Return payever client id config value
     *
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->getMethodValue(self::KEY_CLIENT_SECRET);
    }

    /**
     * Return payever slug config value
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->getMethodValue(self::KEY_SLUG);
    }

    /**
     * Return payever locale config value
     *
     * @return mixed
     */
    public function getLocale()
    {
        return $this->getMethodValue(self::KEY_LOCALE);
    }

    /**
     * Return payever min order total config value
     *
     * @return mixed
     */
    public function getMinOrderTotal($storeId)
    {
        return $this->getValue(self::KEY_MIN_ORDER_TOTAL, $storeId);
    }

    /**
     * Return payever max order total config value
     *
     * @return mixed
     */
    public function getMaxOrderTotal($storeId)
    {
        return $this->getValue(self::KEY_MAX_ORDER_TOTAL, $storeId);
    }

    /**
     * Return array of allowed countries code
     *
     * @param $storeId
     * @return array
     */
    public function getAllowedCountry($storeId)
    {
        $options = $this->unserialize->unserialize($this->getValue(self::KEY_OPTIONS, $storeId));
        return is_array($options['countries']) ? $options['countries'] : [];
    }

    /**
     * Return array of allowed currencies code
     *
     * @param $storeId
     * @return array
     */
    public function getAllowedCurrencies($storeId)
    {
        $options = $this->unserialize->unserialize($this->getValue(self::KEY_OPTIONS, $storeId));
        return is_array($options['currencies']) ? $options['currencies'] : [];
    }

    /**
     * Return methods to hide
     *
     * @return array
     */
    public function getAllowedToHideMethods()
    {
        return array(
            'payever_santander_invoice_de',
        );
    }

    /**
     * Hide payever methods
     *
     * @param $methods
     */
    public function setPayeverHiddenMethod($method)
    {
        $newHiddenMethod = $this->addMethodPrefix($method);
        $oldHiddenMethods = $this->getPayeverHiddenMethods();
        if (in_array($newHiddenMethod, $this->getAllowedToHideMethods())
            && !in_array($newHiddenMethod, $oldHiddenMethods)) {
            $oldHiddenMethods[] = $newHiddenMethod;
            $this->customerSession->setPayeverHiddenMethods($oldHiddenMethods);
        }
    }

    /**
     * Return payever hidden methods
     *
     * @return array
     */
    public function getPayeverHiddenMethods()
    {
        return $this->customerSession->getPayeverHiddenMethods() ? $this->customerSession->getPayeverHiddenMethods() : array();
    }
}
