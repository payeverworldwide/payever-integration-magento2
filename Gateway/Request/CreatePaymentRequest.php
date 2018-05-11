<?php
namespace Payever\Payever\Gateway\Request;

use Payever\Payever\Gateway\Config\Config;
use Payever\Payever\Gateway\Helper\SubjectReader;
use Payever\Payever\Helper\Formatter;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Json\Helper\Data;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;

/**
 * Class CreatePaymentRequest
 * @package Payever\Payever\Gateway\Request
 */
class CreatePaymentRequest implements BuilderInterface
{

    use Formatter;

    /**
     * Extension name for getting plugin verion
     */
    const EXTENSION_NAME = 'Payever_Payever';

    /**
     *
     */
    const RESPONSE_URL = 'payever/response/%s';

    /**
     * @var array
     */
    private $required = [
        'postcode' => 'Zip code',
        'lastname' => 'Last name',
        'street' => 'Street',
        'city' => 'City',
        'email' => 'Email',
        'telephone' => 'Phone Number',
        'country_id' => 'Country',
        'firstname' => 'First Name'
    ];

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var UrlInterface
     */
    private $urlHelper;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * CreatePaymentRequest constructor.
     * @param Config $config
     * @param Image $imageHelper
     * @param Data $jsonHelper
     * @param ModuleListInterface $moduleList
     * @param UrlInterface $urlHelper
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        Image $imageHelper,
        Data $jsonHelper,
        ModuleListInterface $moduleList,
        UrlInterface $urlHelper,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->imageHelper = $imageHelper;
        $this->jsonHelper = $jsonHelper;
        $this->moduleList = $moduleList;
        $this->urlHelper = $urlHelper;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param $quote
     * @return array
     */
    private function getQuoteItems($quote)
    {
        $items = $quote->getAllVisibleItems();
        $cartItems = [];
        $discount = 0;
        foreach ($items as $item) {
            $product = $item->getProduct();
            $discount -= $item->getDiscountAmount();
            $imageURl = $this->imageHelper->init($product, 'product_small_image')->getUrl();
            $cartItems[] = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'price' => $this->formatPrice($item->getPriceInclTax()),
                'priceNetto' => $this->formatPrice($item->getPrice()),
                'vatRate' => $this->formatPrice($item->getTaxPercent()),
                'quantity' => round($item->getQty()),
                'description' => strip_tags($product->getShortDescription()),
                'thumbnail' => $imageURl,
                'url' => $product->getProductUrl()
            ];
        }

        if ($discount != 0) {
            $name = ($discount > 0) ? __("Surcharge") : __("Discount");
            $cartItems[] = [
                'name' => $name,
                'price' => $discount,
                'quantity' => 1
            ];
        }

        return $cartItems;
    }

    /**
     * @param Address $address
     */
    private function validateAddress(Address $address)
    {
        foreach ($this->required as $code => $label) {
            if (!$address->getData($code)) {
                throw new \InvalidArgumentException(sprintf('%s is a required field', $label));
            }
        }
    }

    /**
     * @param $method
     * @return bool
     */
    private function isValidMethod($method)
    {
        return (bool) $this->config->isPayeverMethod($method);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $quote = $this->subjectReader->readQuote($buildSubject);

        $this->validateAddress($quote->getBillingAddress());

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId()->save();
        }

        $address = $quote->getBillingAddress();
        $street = trim(implode(' ', $address->getStreet()));
        $paymentMethod = $quote->getPayment()->getMethod();

        $ret = [
            'payment_method' => $this->config->removeMethodPrefix($paymentMethod),
            'channel' => $this->config->getModuleChannel(),
            'amount' => $quote->getGrandTotal(),
            'fee' => $quote->getShippingAddress()->getShippingAmount(),
            'order_id' => $quote->getReservedOrderId(),
            'currency' => $quote->getQuoteCurrencyCode(),
            'cart' => $this->jsonHelper->jsonEncode($this->getQuoteItems($quote)),
            'first_name' =>  $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'birthdate' => $quote->getCustomerDob(),
            'city' => $address->getCity(),
            'zip' => $address->getPostcode(),
            'street' => $street,
            'country' => $address->getCountryId(),
            'email' => $address->getEmail(),
            'phone' => $address->getTelephone(),
            'success_url' => $this->getUrl('success', ['payment_id' => '--PAYMENT-ID--']),
            'failure_url' => $this->getUrl('failure', ['payment_id' => '--PAYMENT-ID--']),
            'cancel_url' => $this->getUrl('cancel', ['payment_id' => '--CALL-ID--']),
            'notice_url' => $this->getUrl('notice', ['payment_id' => '--PAYMENT-ID--']),
            'pending_url' => $this->getUrl('success', ['payment_id' => '--PAYMENT-ID--', 'is_pending' => 'true']),
            'plugin_version' => $this->getPluginVersion()
        ];

        return $ret;
    }

    /**
     * Getting version of the module
     *
     * @return mixed
     */
    private function getPluginVersion()
    {
        return $this->moduleList->getOne(self::EXTENSION_NAME)['setup_version'];
    }

    /**
     * @param $type
     * @param $query
     * @return string
     */
    private function getUrl($type, $query)
    {
        return $this->urlHelper->getUrl(
            sprintf(self::RESPONSE_URL, $type),
            ['_query' => $query, '_secure' => true]
        );
    }
}
