<?php

namespace Payever\Payever\Gateway\Command;

use Payever\Payever\Model\Adapter\PayeverAdapter;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\CommandInterface;
use Payever\Payever\Gateway\Request\CreatePaymentRequest;
use Payever\Payever\Gateway\Http\TransferFactory;
use Magento\Framework\Exception\LocalizedException;
use Payever\Payever\Gateway\Validator\CreatePaymentValidator;
use Payever\Payever\Gateway\Response\CreatePaymentHandler;
use Payever\Payever\Gateway\Http\Client\CreatePayment;
use Payever\Payever\Gateway\Config\Config;

/**
 * Class GetPaymentNonceCommand
 */
class CreatePaymentCommand implements CommandInterface
{

    /**
     * @var PayeverAdapter
     */
    private $adapter;

    /**
     * @var ArrayResultFactory
     */
    private $resultFactory;

    /**
     * @var CreatePaymentRequest
     */
    private $requestBuilder;

    /**
     * @var CreatePaymentRequest
     */
    private $transferFactory;

    /**
     * @var CreatePayment
     */
    private $client;

    /**
     * @var CreatePaymentHandle
     */
    private $handler;

    /**
     * @var CreatePaymentValidator
     */
    private $createPaymentValidator;

    /**
     * @var Config
     */
    private $config;

    /**
     * CreatePaymentCommand constructor.
     * @param PayeverAdapter $adapter
     * @param ArrayResultFactory $resultFactory
     * @param CreatePaymentRequest $requestBuilder
     * @param TransferFactory $transferFactory
     * @param CreatePaymentValidator $createPaymentValidator
     * @param CreatePayment $client
     * @param CreatePaymentHandler $handler
     * @param Config $config
     */
    public function __construct(
        PayeverAdapter $adapter,
        ArrayResultFactory $resultFactory,
        CreatePaymentRequest $requestBuilder,
        TransferFactory $transferFactory,
        CreatePaymentValidator $createPaymentValidator,
        CreatePayment $client,
        CreatePaymentHandler $handler,
        Config $config
    ) {
        $this->adapter = $adapter;
        $this->resultFactory = $resultFactory;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->createPaymentValidator = $createPaymentValidator;
        $this->client = $client;
        $this->handler = $handler;
        $this->config = $config;
    }

    /**
     * Implement Create payment command for API call
     *
     * @param array $commandSubject
     * @return mixed
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);
        $result = $this->createPaymentValidator->validate(['response' => $response]);

        if (!$result->isValid()) {
            throw new LocalizedException(__(implode("\n", $result->getFailsDescription())));
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }

        return $this->resultFactory->create(['array' => ['url' => $response['object']['redirect_url']]]);
    }

    /**
     * @param $url
     * @return string
     */
    public function addLocaleToUrl($url)
    {
        $locale = $this->config->getLocale();
        if (!empty($locale)) {
            $url .= '?_locale=' . $locale;
        }

        return $url;
    }
}
