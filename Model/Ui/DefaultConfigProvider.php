<?php

namespace Payever\Payever\Model\Ui;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\UrlInterface;
use Payever\Payever\Model\Adminhtml\Source\Environment;
use Payever\Payever\Model\Api\PayeverApi;

/**
 * Class DefaultConfigProvider
 * @package Payever\Payever\Model\Ui
 */
class DefaultConfigProvider implements ConfigProviderInterface
{
    const CODE = 'default';
    const CREATE_PAYMENT_URL = 'payever/payment/createpayment/method/%s';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var UrlInterface
     */
    private $urlHelper;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * DefaultConfigProvider constructor.
     * @param ConfigInterface $config
     * @param Repository $assetRepo
     * @param UrlInterface $urlHelper
     * @param Environment $environment
     */
    public function __construct(
        ConfigInterface $config,
        Repository $assetRepo,
        UrlInterface $urlHelper,
        Environment $environment
    ) {
        $this->config = $config;
        $this->assetRepo = $assetRepo;
        $this->urlHelper = $urlHelper;
        $this->environment = $environment;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config->getValue('active') ? [
            'payment' => [
                static::CODE => [
                    'active' => $this->config->getValue('active'),
                    'title' => $this->getTitle(),
                    'description' => $this->config->getMethodValue('display_payment_description') ?
                        $this->config->getValue('description'):
                        '',
                    'iconUrl' => $this->config->getMethodValue('display_payment_icon') ?
                        $this->getIconUrl():
                        '',
                    'createPaymentUrl' => $this->getCreatePaymentUrl(),
                    'modeIntegration' => $this->config->getMethodValue('mode_integration') ?
                        $this->config->getMethodValue('mode_integration'):
                        'iframe'
                ]
            ]
        ] : [];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->config->getValue('title')
            . ($this->config->getMethodValue('environment') != PayeverApi::ENVIRONMENT_PRODUCTION ?
                ' - ' . $this->environment->getLabel($this->config->getMethodValue('environment')) . ' ' . __('MODE'):
                ''
            );
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return $this->assetRepo->createAsset('Payever_Payever::images/base/' . static::CODE . '.png')->getUrl();
    }

    /**
     * @return string
     */
    public function getCreatePaymentUrl()
    {
        return $this->urlHelper->getUrl(sprintf(static::CREATE_PAYMENT_URL, static::CODE));
    }
}
