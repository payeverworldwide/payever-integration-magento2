<?php

namespace Payever\Payever\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Mode
 * @package Payever\Payever\Model\Adminhtml\Source
 */
class Locale implements ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Default')],
            ['value' => 'en', 'label' => __('English')],
            ['value' => 'de', 'label' => __('Deutsch')],
            ['value' => 'es', 'label' => __('Español')],
            ['value' => 'no', 'label' => __('Norsk')],
            ['value' => 'da', 'label' => __('Dansk')],
            ['value' => 'sv', 'label' => __('Svenska')]
        ];
    }
}
