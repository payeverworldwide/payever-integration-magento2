<?php
namespace Payever\Payever\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Custom
 * @package Payever\Payever\Logger\Handler
 */
class Custom extends Base
{
    protected $fileName = '/var/log/payever.log';
    protected $loggerType = Logger::DEBUG;
}
