<?php

namespace Payever\Payever\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Payever\Payever\Model\Api\PayeverApi;

/**
 * Class Environment
 * @package Payever\Payever\Model\Adminhtml\Source
 */
class Environment implements ArrayInterface
{

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => PayeverApi::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ],
            [
                'value' => PayeverApi::ENVIRONMENT_PRODUCTION,
                'label' => 'Live'
            ]
        ];
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getLabel($value)
    {
        foreach ($this->toOptionArray() as $v) {
            if ($v['value'] == $value) {
                return $v['label'];
            }
        }
    }
}
