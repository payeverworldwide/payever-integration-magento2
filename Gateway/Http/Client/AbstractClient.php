<?php

namespace Payever\Payever\Gateway\Http\Client;

use Payever\Payever\Model\Adapter\PayeverAdapter;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Payever\Payever\Gateway\Helper\RetrieveResponseReader;

/**
 * Class AbstractClient
 * @package Payever\Payever\Gateway\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * @var PayeverAdapter
     */
    protected $adapter;

    /**
     * AbstractClient constructor.
     * @param Logger $logger
     * @param PayeverAdapter $adapter
     */
    public function __construct(
        Logger $logger,
        PayeverAdapter $adapter
    ) {
        $this->logger = $logger;
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $this->logger->debug([$message]);
            throw new ClientException($message);
        } finally {
            if ($response['object'] instanceof RetrieveResponseReader) {
                $log['response'] = $response['object']->getResponse();
            } else {
                $log['response'] = (array) $response['object'];
            }

            $this->logger->debug($log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @return mixed
     */
    abstract protected function process(array $data);
}
