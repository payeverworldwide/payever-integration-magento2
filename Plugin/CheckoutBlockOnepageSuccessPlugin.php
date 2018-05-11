<?php

namespace Payever\Payever\Plugin;

use Magento\Framework\App\RequestInterface;

/**
 * Class CheckoutBlockOnepageSuccessPlugin
 * @package Payever\Payever\Plugin
 */
class CheckoutBlockOnepageSuccessPlugin
{
    const REPLACE_TEMPLATE_TARGET = 'Magento_Checkout::success.phtml';
    const REPLACE_TEMPLATE_RESULT = 'Payever_Payever::checkout/pending.phtml';

    /**
     * Request instance
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Override template for pending payment state
     *
     * @param $subject
     * @param $template
     * @return string
     */
    public function beforeSetTemplate($subject, $template)
    {
        if ($this->request->getParam('is_pending') && $template == self::REPLACE_TEMPLATE_TARGET) {
            $template = self::REPLACE_TEMPLATE_RESULT;
        }

        return [$template];
    }
}
