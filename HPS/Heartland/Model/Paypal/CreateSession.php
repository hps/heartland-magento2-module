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

//use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;

/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class CreateSession extends \Magento\Framework\Model\AbstractModel {

    /**
     * @var bool|\HpsServicesConfig
     */
    protected $heartlandApi = false;
    private $request = false;
    private $checkoutSession;
    private $isSandboxMode = 0;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteItemRepository
     */
    private $quoteItemRepository;

    public function __construct(
    \Magento\Framework\Model\Context $context, \HpsServicesConfig $hpsConfig, \Magento\Framework\App\Request\Http $request, Session $checkoutSession, \Magento\Quote\Api\CartRepositoryInterface $quoteRepository, \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository
    ) {
        $this->heartlandApi = $hpsConfig;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->isSandboxMode = HPS_DATA::getConfig('payment/hps_paypal/use_sandbox');
    }

    /*
     * Create new paypal session using HPS portico service
     */
    public function createPaypalSession() {
        $response = array();
        $errorMessage = '';
        try {
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
                $buyer = new \HpsBuyerData();
                $buyer->returnUrl = HPS_DATA::getBaseUrl() . 'checkout';
                $buyer->cancelUrl = $buyer->returnUrl;


                // Create PaymentInfo
                $payment = new \HpsPaymentData();
                $payment->subtotal = HPS_DATA::formatNumber2Precision($quote->getSubtotal());
                $payment->shippingAmount = HPS_DATA::formatNumber2Precision($quote->getShippingAddress()->getShippingAmount());
                $payment->taxAmount = HPS_DATA::formatNumber2Precision($quote->getShippingAddress()->getTaxAmount());
                $payment->paymentType = 'Sale';


                // Create ShippingInfo
                $shipping = new \HpsShippingInfo();
                $shipping->name = $shippingAdress->getName();
                $shipping->address = new \HpsAddress();
                $shipping->address->address = $shippingAdress->getStreetLine(1) . ', ' . $shippingAdress->getStreetLine(2);
                $shipping->address->city = $shippingAdress->getCity();
                $shipping->address->state = $shippingAdress->getRegionCode();
                $shipping->address->zip = $shippingAdress->getPostcode();
                $shipping->address->country = $shippingAdress->getCountry();

                // Line Items
                $items = array();
                $itemNumber = 1;

                //get all items
                $quoteItems = $this->quoteItemRepository->getList($quoteId);
                foreach ($quoteItems as $index => $quoteItem) {
                    $item1 = new \HpsLineItem();
                    $item1->name = filter_var($quoteItem->getName(), FILTER_SANITIZE_SPECIAL_CHARS);
                    $item1->description = $quoteItem->getDescription();
                    $item1->number = $itemNumber++;
                    $item1->amount = HPS_DATA::formatNumber2Precision($quoteItem->getBaseRowTotal());
                    $item1->quantity = $quoteItem->getQty();
                    $item1->taxAmount = HPS_DATA::formatNumber2Precision($quoteItem->getTaxAmount());
                    $items[] = $item1;
                }


                // Create session
                $config = new \HpsServicesConfig();
                if ($this->isSandboxMode == 1) {
                    $config->username = HPS_DATA::getConfig('payment/hps_paypal/username');
                    $config->password = HPS_DATA::getConfig('payment/hps_paypal/password');
                    $config->deviceId = HPS_DATA::getConfig('payment/hps_paypal/device_id');
                    $config->licenseId = HPS_DATA::getConfig('payment/hps_paypal/license_id');
                    $config->siteId = HPS_DATA::getConfig('payment/hps_paypal/site_id');
                    $config->soapServiceUri = 'https://api-uat.heartlandportico.com/paymentserver.v1/PosGatewayService.asmx?wsdl';
                } else {
                    $config->secretApiKey = HPS_DATA::getConfig('payment/hps_paypal/secretapikey');
                }
                //call portico service
                $service = new \HpsPayPalService($config);
                $response = $service->createSession($amount, $currency, $buyer, $payment, $shipping, $items);
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
        $finalResponse = array();
        if (!empty($porticoResponse)) {
            if ($porticoResponse->responseCode == 0) {
                $finalResponse = array(
                    'status' => 'success',
                    'message' => __('Paypal session created successfully!'),
                    'sessiondetails' => array(
                        'sessionId' => $porticoResponse->sessionId,
                        'redirectUrl' => $porticoResponse->redirectUrl,
                        'transactionId' => $porticoResponse->transactionId
                    )
                );
            } else {
                $finalResponse = array(
                    'status' => 'error',
                    'message' => __('Error in creating session ') . $message,
                    'sessiondetails' => array()
                );
            }
        } else {
            $finalResponse = array(
                'status' => 'error',
                'message' => __('Order details not found! ') . $message,
                'sessiondetails' => array()
            );
        }
        return $finalResponse;
    }

}
