<?php

namespace Payever\Payever\Controller\Adminhtml\Synchronize;

use Payever\Payever\Model\Helper\Synchronize\SettingsPayever;
use Magento\Framework\Controller\Result\JsonFactory ;
use Magento\Backend\App\Action;

/**
 * Class Settings
 * @package Payever\Payever\Controller\Adminhtml\Synchronize
 */
class Settings extends Action
{
    /**
     * @var SettingsPayever
     */
    private $settingsPayever;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @param Action\Context $context
     * @param SettingsPayever $settingsPayever
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        SettingsPayever $settingsPayever,
        JsonFactory $jsonFactory
    ) {
        $this->settingsPayever = $settingsPayever;
        $this->jsonFactory = $jsonFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $jsonResult = $this->jsonFactory->create();
        try {
            $this->settingsPayever->execute();
            $jsonResult
                ->setData([
                    'result' => 'success',
                    'message' => __('Settings were synchronized')
                ]);
        } catch (\Exception $e) {
            $jsonResult
                ->setData([
                    'result' => 'error',
                    'message' => $e->getMessage()
                ]);
        }

        return $jsonResult;
    }
}
