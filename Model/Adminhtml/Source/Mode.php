<?php

namespace Payever\Payever\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Mode
 * @package Payever\Payever\Model\Adminhtml\Source
 */
class Mode implements ArrayInterface
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
                'value' => 'iframe',
                'label' => __('Iframe')
            ],
            [
                'value' => 'redirect',
                'label' => __('Redirect to payever')
            ],
            [
                'value' => 'redirect_iframe',
                'label' => __('Redirect & Iframe')
            ]
        ];
    }
}
