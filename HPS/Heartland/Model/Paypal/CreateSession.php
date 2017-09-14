<?php

/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Model\Paypal;

use \HPS\Heartland\Helper\Customer;
use \HPS\Heartland\Helper\Admin;
use \HPS\Heartland\Helper\Db;
use \HPS\Heartland\Helper\Data as HPS_DATA;
use Magento\Checkout\Model\Session;

/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class CreateSession extends \Magento\Framework\Model\AbstractModel {

    /**
     * @var bool|\HpsServicesConfig
     */
    private $heartlandApi = false;
    private $checkoutSession;
    private $isSandboxMode = 0;
    private $buyer = null;
    private $paymentInfo = null;
    private $shipping = null;
    private $servicesConfig = null;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteItemRepository
     */
    private $quoteItemRepository;
    private $cartManagement;

    public function __construct(
    \HpsServicesConfig $hpsConfig, Session $checkoutSession, \Magento\Quote\Api\CartRepositoryInterface $quoteRepository, \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository, \HpsBuyerData $buyer, \HpsPaymentData $paymentInfo, \HpsShippingInfo $shipping, \HpsServicesConfig $hpsServicesConfig, \HpsAddress $address, \Magento\Quote\Api\CartManagementInterface $cartManagement
    ) {
        $this->heartlandApi = $hpsConfig;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->isSandboxMode = HPS_DATA::getConfig('payment/hps_paypal/use_sandbox');
        $this->buyer = $buyer;
        $this->paymentInfo = $paymentInfo;
        $this->shipping = $shipping;
        $this->shipping->address = $address;
        $this->servicesConfig = $hpsServicesConfig;
        $this->cartManagement = $cartManagement;
    }

    /*
     * Create new paypal session using HPS portico service
     */

    public function createPaypalSession() {
        $response = [];
        $errorMessage = '';
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            //get the quote details
            $quote = $this->checkoutSession->getQuote();
            $quoteId = $quote->getId();

            if (!empty($quoteId)) {
                $quote = $this->quoteRepository->get($quoteId);
                $shippingAdress = $quote->getShippingAddress();
            }
            //create session when quote details not empty
            if (!empty($quote)) {
                // Amount
                $amount = HPS_DATA::formatNumber2Precision($quote->getBaseGrandTotal());

                // Currency
                $currency = $quote->getQuoteCurrencyCode();

                // Create BuyerInfo
                $this->buyer->returnUrl = HPS_DATA::getBaseUrl() . 'hpsorder/paypal/orderreview?oid=' . $quoteId;
                $this->buyer->cancelUrl = HPS_DATA::getBaseUrl() . 'hpsordercancel/paypal/cancelorder?orderid=' . $quoteId;

                // Create PaymentInfo
                $this->paymentInfo->subtotal = HPS_DATA::formatNumber2Precision($quote->getSubtotal());
                $this->paymentInfo->shippingAmount = HPS_DATA::formatNumber2Precision($quote->getShippingAddress()->getShippingAmount());
                $this->paymentInfo->taxAmount = HPS_DATA::formatNumber2Precision($quote->getShippingAddress()->getTaxAmount());
                $this->paymentInfo->paymentType = 'Sale';

                // Create ShippingInfo
                $this->shipping->name = $shippingAdress->getName();
                $this->shipping->address->address = $shippingAdress->getStreetLine(1) . ', ' . $shippingAdress->getStreetLine(2);
                $this->shipping->address->city = $shippingAdress->getCity();
                $this->shipping->address->state = $shippingAdress->getRegionCode();
                $this->shipping->address->zip = $shippingAdress->getPostcode();
                $this->shipping->address->country = $shippingAdress->getCountry();

                // Line Items
                $items = [];
                $itemNumber = 1;

                //get all items
                $quoteItems = $this->quoteItemRepository->getList($quoteId);
                foreach ($quoteItems as $index => $quoteItem) {
                    $item1 = $objectManager->create('HpsLineItem');
                    $item1->name = filter_var($quoteItem->getName(), FILTER_SANITIZE_SPECIAL_CHARS);
                    $item1->description = $quoteItem->getDescription();
                    $item1->number = $itemNumber++;
                    $item1->amount = HPS_DATA::formatNumber2Precision($quoteItem->getBasePrice());
                    $item1->quantity = $quoteItem->getQty();
                    $item1->taxAmount = HPS_DATA::formatNumber2Precision($quoteItem->getTaxAmount());
                    $items[] = $item1;
                }

                // Create session
                if ($this->isSandboxMode == 1) {
                    $this->servicesConfig->username = HPS_DATA::getConfig('payment/hps_paypal/username');
                    $this->servicesConfig->password = HPS_DATA::getConfig('payment/hps_paypal/password');
                    $this->servicesConfig->deviceId = HPS_DATA::getConfig('payment/hps_paypal/device_id');
                    $this->servicesConfig->licenseId = HPS_DATA::getConfig('payment/hps_paypal/license_id');
                    $this->servicesConfig->siteId = HPS_DATA::getConfig('payment/hps_paypal/site_id');
                    $this->servicesConfig->soapServiceUri = 'https://api-uat.heartlandportico.com/paymentserver.v1/PosGatewayService.asmx?wsdl';
                } else {
                    $this->servicesConfig->secretApiKey = HPS_DATA::getConfig('payment/hps_paypal/secretapikey');
                }
                // Use HTTP proxy
                if (HPS_DATA::getConfig('payment/hps_paypal/use_http_proxy')) {
                    $this->servicesConfig->useProxy = true;
                    $this->servicesConfig->proxyOptions = [
                        'proxy_port' => HPS_DATA::getConfig('payment/hps_paypal/http_proxy_port'),
                        'proxy_host' => HPS_DATA::getConfig('payment/hps_paypal/http_proxy_host'),
                    ];
                }
                // Adding product to order
                $quote->getPayment()->setMethod('paypal');
                $this->cartManagement->placeOrder($quoteId);

                //call portico service
                $service = new \HpsPayPalService($this->servicesConfig);
                $response = $service->createSession($amount, $currency, $this->buyer, $this->paymentInfo, $this->shipping, $items);
            } else {
                $errorMessage = 'Order details not found!';
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }
        return $this->sendResponse($response, $errorMessage);
    }

    /*
     * Process portico response. Send response to the controller
     */

    private function sendResponse($porticoResponse, $message = '') {
        $finalResponse = [];
        if (!empty($porticoResponse) && $porticoResponse->responseCode == 0) {
            $finalResponse = [
                'status' => 'success',
                'message' => __('Paypal session created successfully!'),
                'sessiondetails' => [
                    'sessionId' => $porticoResponse->sessionId,
                    'redirectUrl' => $porticoResponse->redirectUrl,
                    'transactionId' => $porticoResponse->transactionId
                ]
            ];
        } else {
            $finalResponse = [
                'status' => 'error',
                'message' => __('Error in creating session! ') . $message,
                'sessiondetails' => []
            ];
        }
        return $finalResponse;
    }

}
