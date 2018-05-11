<?php

namespace Payever\Payever\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Synchronize extends Field
{

    /**
     * Path to block template
     */
    const SYNCHRONIZE_TEMPLATE = 'system/config/synchronize.phtml';

    /**
     * Set template to itself
     *
     * @return \Payever\Payever\Block\Adminhtml\System\Config\Synchronize
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate(self::SYNCHRONIZE_TEMPLATE);
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
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('payever/synchronize/settings')
            ]
        );

        return $this->_toHtml();
    }
}
