<?php

namespace Payever\Payever\Model\Data;

use Payever\Payever\Api\Data\CreatePaymentResponseInterface;

/**
 * Class CreatePaymentResponse
 * @package Payever\Payever\Model\Data
 */
class CreatePaymentResponse implements CreatePaymentResponseInterface
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $error;

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
