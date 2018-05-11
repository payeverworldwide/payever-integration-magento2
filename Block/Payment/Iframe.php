<?php

namespace Payever\Payever\Block\Payment;

use Magento\Framework\View\Element\Template\Context;
use Payever\Payever\Gateway\Config\Config;
use Magento\Framework\View\Element\Template;

class Iframe extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Config $config
    ) {
        $this->config = $config;
        parent::__construct($context);
    }
}
