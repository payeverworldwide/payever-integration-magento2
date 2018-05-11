<?php

namespace Payever\Payever\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Payever\Payever\Model\Helper\Synchronize\SettingsPayever;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Credentials extends Field
{
    /**
     * @var SettingsPayever
     */
    private $settingsPayever;

    const CREDENTIALS_TEMPLATE = 'system/config/credentials.phtml';

    /**
     * @param Context $context
     * @param SettingsPayever $settingsPayever
     * @param array $data
     */
    public function __construct(
        Context $context,
        SettingsPayever $settingsPayever,
        array $data = []
    ) {
        $this->settingsPayever = $settingsPayever;
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself
     *
     * @return \Payever\Payever\Block\Adminhtml\System\Config\Credentials
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate(self::CREDENTIALS_TEMPLATE);
        }
        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $buttonLabel = $this->settingsPayever->isDemoKeys() ?
            __('Reset live API keys'):
            __('Set up sandbox API keys');

        $ajaxUrl = $this->_urlBuilder->getUrl('payever/synchronize/credentials');
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $ajaxUrl,
                'disabled' => $this->settingsPayever->isDemoKeys() && !$this->settingsPayever->isLiveKeysExists()
            ]
        );

        return $this->_toHtml();
    }
}
