<?php

namespace Payever\Payever\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Magento\Config\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Fieldset;

/**
 * Class Payment
 * @package Payever\Payever\Block\Adminhtml\System\Config\Fieldset
 */
class Payment extends Fieldset
{
    const PAYMENT_ACTIVE_PATH_PATTERN = 'payment/%s/active';

    /**
     * @var Config
     */
    protected $backendConfig;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Config $backendConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        array $data = []
    ) {
        $this->backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Return header title part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {

        $group = $element->getData('group')['id'];
        $isActive = $this->backendConfig->
                getConfigDataValue(sprintf(self::PAYMENT_ACTIVE_PATH_PATTERN, $group));

        return '<a id="' .
            $element->getHtmlId() .
            '-head" href="#' .
            $element->getHtmlId() .
            '-link" onclick="Fieldset.toggleCollapse(\'' .
            $element->getHtmlId() .
            '\', \'' .
            $this->getUrl(
                '*/*/state'
            ) . '\'); return false;">' . $element->getLegend() .
            ($isActive ?
            '<span style="color:green; display: inline-block; float: right;">'.__('enabled').'</span>' :
            '<span style="color:red; display: inline-block; float: right;">'.__('disabled').'</span>' ) .
            '</a>';
    }
}
