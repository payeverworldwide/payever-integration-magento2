<?php

namespace Payever\Payever\Model\Helper\Synchronize;

use Payever\Payever\Gateway\Config\Config;
use Payever\Payever\Model\Adapter\PayeverAdapterFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\App\Cache\Manager;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Unserialize\Unserialize;

/**
 * Class SettingsPayever
 * @codeCoverageIgnore
 */
class SettingsPayever
{
    const DEFAULT_CURRENCY = 'EUR';

    const SETTING_DEMO_ENVIRONMENT = 'payever_settings/demo/environment';
    const SETTING_DEMO_CLIENT_ID = 'payever_settings/demo/client_id';
    const SETTING_DEMO_CLIENT_SECRET = 'payever_settings/demo/client_secret';
    const SETTING_DEMO_SLUG = 'payever_settings/demo/slug';
    const SETTING_BACKUP_LIVE = 'payever_settings/demo/backup_live_keys';

    private $cache = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PayeverAdapterFactory
     */
    private $payeverAdapter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var Unserialize
     */
    private $unserialize;

    /**
     * SettingsPayever constructor.
     * @param Config $config
     * @param PayeverAdapterFactory $payeverAdapter
     * @param StoreManagerInterface $storeManager
     * @param ResourceConfig $resourceConfig
     * @param Encryptor $encryptor
     * @param Manager $cacheManager
     * @param Unserialize $unserialize
     */
    public function __construct(
        Config $config,
        PayeverAdapterFactory $payeverAdapter,
        StoreManagerInterface $storeManager,
        ResourceConfig $resourceConfig,
        Encryptor $encryptor,
        Manager $cacheManager,
        Unserialize $unserialize
    ) {
    
        $this->config = $config;
        $this->payeverAdapter = $payeverAdapter;
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->cacheManager = $cacheManager;
        $this->unserialize = $unserialize;
    }

    /**
     * Synchronize payever and magento settings
     */
    public function execute()
    {
        $stores = $this->getStores();

        $defaultScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $storeScope = ScopeInterface::SCOPE_STORE;

        $defaultSettings = $this->getScopeSettings(
            $defaultScope,
            0
        );

        $this->updatePaymentSettings($defaultScope, 0, $defaultSettings);

        foreach ($stores as $storeId => $storeSettings) {
            if ($this->isSettingsSame($defaultSettings, $storeSettings)) {
                continue;
            }
            $this->updatePaymentSettings($storeScope, $storeId, $storeSettings);
        }

        $this->cleanCache();
    }

    /**
     * Compare settings arrays
     *
     * @param $settings1
     * @param $settings2
     * @return bool
     */
    public function isSettingsSame($settings1, $settings2)
    {
        return ($settings1 == $settings2);
    }

    /**
     * Save config value
     *
     * @param $key
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function setConfigValue($key, $value, $scope = 'default', $scopeId = 0)
    {
        $this->resourceConfig->saveConfig(
            $key,
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * Update method settings by store id and method name
     *
     * @param $method
     * @param $settings
     * @param $scope
     * @param $scopeId
     */
    private function updateMethodConfig($method, $settings, $scope, $scopeId)
    {
        foreach ($settings as $key => $value) {
            $this->setConfigValue("payment/{$method}/{$key}", $value, $scope, $scopeId);
        }
    }

    /**
     * Update whole settings for store id
     *
     * @param $scope
     * @param $scopeId
     * @param $storeSettings
     */
    private function updatePaymentSettings($scope, $scopeId, $storeSettings)
    {
        $settings = $this->getPaymentSettings($storeSettings);
        foreach ($settings as $method => $options) {
            $method = $this->config->addMethodPrefix($method);
            $this->updateMethodConfig($method, $options, $scope, $scopeId);
        }
    }

    /**
     * Get payment settings from payever account
     *
     * @param $storeSettings
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getPaymentSettings($storeSettings)
    {
        $settings = $this->payeverAdapter->create()
            ->setUpCredentials(
                $storeSettings['client_id'],
                $storeSettings['client_secret']
            )
            ->getPaymentSettings(
                $storeSettings['slug'],
                $storeSettings['currency'],
                $storeSettings['lang']
            );

        $data = [];
        foreach ($settings['result'] as $setting) {
            if (is_array($setting['options']['currencies'])
                && count($setting['options']['currencies']) == 1
                && array_values($setting['options']['currencies'])[0] != $storeSettings['currency']
            ) {
                $settingCurrency = $this->getPaymentSettingsForMethod(
                    $setting['payment_method'],
                    $storeSettings['slug'],
                    array_values($setting['options']['currencies'])[0],
                    $storeSettings['lang']
                );
                if ($settingCurrency) {
                    $setting = $settingCurrency;
                }
            }

            $data[$setting['payment_method']] = [
                'active' => (int)$setting['status'] == 'active',
                'title' => $setting['name'],
                'description' => strip_tags($setting['description_offer']),
                'min_payever_order_total' => $setting['min'],
                'max_payever_order_total' => $setting['max'],
                'options' => serialize($setting['options'])
            ];
        }

        return $data;
    }

    /**
     * Get settings for method, currency, lang, slug
     *
     * @param $method
     * @param $slug
     * @param $currency
     * @param $lang
     * @return bool
     */
    private function getPaymentSettingsForMethod($method, $slug, $currency, $lang)
    {
        $cacheKey = $slug . $currency . $lang;
        if (empty($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->payeverAdapter->create()->getPaymentSettings($slug, $currency, $lang);
        }

        foreach ($this->cache[$cacheKey]['result'] as $setting) {
            if ($setting['payment_method'] == $method) {
                return $setting;
            }
        }
        return false;
    }

    /**
     * Get whole stores from the store id
     *
     * @return array
     */
    private function getStores()
    {
        $storesData = [];
        $stores = $this->storeManager->getStores($withDefault = false);

        foreach ($stores as $store) {
            $storesData[$store->getId()] = $this->getScopeSettings(
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return $storesData;
    }

    private function getScopeSettings($scope, $scopeId)
    {
        $scopeConfig = $this->config->getScopeConfig();

        $locale = $scopeConfig->getValue(
            'general/locale/code',
            $scope,
            $scopeId
        );
        $currency = $scopeConfig->getValue(
            'currency/options/base',
            $scope,
            $scopeId
        );
        $slug = $scopeConfig->getValue(
            'payment/payever/slug',
            $scope,
            $scopeId
        );
        $clientId = $scopeConfig->getValue(
            'payment/payever/client_id',
            $scope,
            $scopeId
        );
        $clientSecret = $scopeConfig->getValue(
            'payment/payever/client_secret',
            $scope,
            $scopeId
        );

        return [
            'currency' => $currency,
            'lang' => substr($locale, 0, 2),
            'slug' => $slug,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ];
    }

    public function getDemoClientId($encrypt = true)
    {
        $value = $this->config->getScopeConfig()->getValue(self::SETTING_DEMO_CLIENT_ID);
        if ($encrypt) {
            $value = $this->encryptor->encrypt($value);
        }
        return $value;
    }

    public function getDemoClientSecret($encrypt = true)
    {
        $value = $this->config->getScopeConfig()->getValue(self::SETTING_DEMO_CLIENT_SECRET);
        if ($encrypt) {
            $value = $this->encryptor->encrypt($value);
        }
        return $value;
    }

    public function getDemoSlug()
    {
        return $this->config->getScopeConfig()->getValue(self::SETTING_DEMO_SLUG);
    }

    public function getDemoEnvironment()
    {
        return \Payever\Payever\Model\Api\PayeverApi::ENVIRONMENT_SANDBOX;
    }

    public function getPathToBackupLive()
    {
        return $this->config->getScopeConfig()->getValue(self::SETTING_BACKUP_LIVE);
    }

    public function setUpDemoKeys()
    {

        $this->setConfigValue(
            self::SETTING_BACKUP_LIVE,
            serialize([
                'id' => $this->config->getClientId(),
                'secret' => $this->config->getClientSecret(),
                'slug' => $this->config->getSlug(),
                'environment' => $this->config->getEnvironment()
            ])
        );

        $this->setConfigValue(
            'payment/payever/client_id',
            $this->getDemoClientId(true)
        );

        $this->setConfigValue(
            'payment/payever/client_secret',
            $this->getDemoClientSecret(true)
        );

        $this->setConfigValue(
            'payment/payever/slug',
            $this->getDemoSlug()
        );

        $this->setConfigValue(
            'payment/payever/environment',
            $this->getDemoEnvironment()
        );

        $this->cleanCache();
    }

    public function setUpLiveKeys()
    {
        $backedUpSettings = $this->getBackUpKeys();

        $this->setConfigValue(
            'payment/payever/client_id',
            $backedUpSettings['id']
        );

        $this->setConfigValue(
            'payment/payever/client_secret',
            $backedUpSettings['secret']
        );

        $this->setConfigValue(
            'payment/payever/slug',
            $backedUpSettings['slug']
        );

        $this->setConfigValue(
            'payment/payever/environment',
            $backedUpSettings['environment']
        );
        $this->cleanCache();
        $this->execute();
    }

    public function getBackUpKeys()
    {
        $scopeConfig = $this->config->getScopeConfig();
        return $this->unserialize->unserialize($scopeConfig->getValue(
            self::SETTING_BACKUP_LIVE,
            'default',
            0
        ));
    }

    public function isDemoKeys()
    {
        if ($this->getDemoClientId(false) == $this->encryptor->decrypt($this->config->getClientId()) &&
            $this->getDemoClientSecret(false) == $this->encryptor->decrypt($this->config->getClientSecret()) &&
            $this->getDemoSlug() == $this->config->getSlug() &&
            $this->getDemoEnvironment() == $this->config->getEnvironment()
        ) {
            return true;
        }

        return false;
    }

    public function isLiveKeysExists()
    {
        $bs = $this->getBackUpKeys();
        return $bs['id'] && $bs['secret'] && $bs['slug'] ? true : false;
    }

    public function cleanCache()
    {
        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
        $this->cacheManager->clean($this->cacheManager->getAvailableTypes());
    }
}
