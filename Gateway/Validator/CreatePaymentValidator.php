<?php

namespace Payever\Payever\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class CreatePaymentValidator extends AbstractValidator
{
    const REDIRECT_URL = 'redirect_url';

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {

        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response']['object'];

        if ($this->isRedirectUrlExist($response)) {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                [__('Redirect url is not exists')]
            );
        }
    }

    /**
     * @param array $response
     * @return bool
     */
    private function isRedirectUrlExist(array $response)
    {
        return isset($response[self::REDIRECT_URL]);
    }
}
