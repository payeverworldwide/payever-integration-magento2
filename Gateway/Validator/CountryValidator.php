<?php

namespace Payever\Payever\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Gateway\Validator\AbstractValidator;

/**
 * Class CountryValidator
 * @package Magento\Payment\Gateway\Validator
 * @api
 */
class CountryValidator extends AbstractValidator
{
    /**
     * @var \Magento\Payment\Gateway\ConfigInterface
     */
    private $config;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = false;

        $storeId = $validationSubject['storeId'];
        $country = $validationSubject['country'];

        $countries = $this->config->getAllowedCountry($storeId);
        if (in_array($country, $countries)) {
            $isValid = true;
        }

        return $this->createResult($isValid);
    }
}
