<?php
/**
 * Copyright (c) 2016.
 * Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Charlie Simmons <charles.simmons@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */


/*
 * @method getOrder()
 *
 */
namespace HPS\Heartland\Model;

use \HPS\Heartland\Model\StoredCard as HPS_STORED_CARDS;
use \HPS\Heartland\Helper\Data as HPS_DATA;

/**
 * Class Payment
 * \HPS\Heartland\Model\Payment
 *
 * @package HPS\Heartland\Model
 */
class Payment extends \Magento\Payment\Model\Method\Cc {
    /**
     *
     */
    const CODE = 'hps_heartland';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_code = self::CODE;
    /**
     * @var bool
     */
    public $_token_value = false;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canCapturePartial = true;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var int
     */
    protected $_save_token_value = 0;
    /**
     * @var bool|\HpsServicesConfig
     */
    protected $_heartlandApi = false;
    /**
     * @var null
     */
    protected $storeId = null;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;
    /**
     * @var null
     */
    protected $_minAmount = null;
    /**
     * @var array
     */
    protected $_supportedCurrencyCodes = array('USD');
    /**
     * @var array
     */
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    /**
     * @var bool
     */
    protected $_context = false;
    /**
     * @var array
     */
    protected $_heartlandConfigFields = [
        'active'             => false,
        'cctypes'            => false,
        'debug'              => false,
        'fraudprotection'    => false,
        'fraud_email'        => false,
        'fraud_notification' => false,
        'order_status'       => false,
        'payment_action'     => false,
        'private_key'        => false,
        'public_key'         => false,
        'title'              => false,
        'use_vault'          => false];

    /**
     * Payment constructor.
     *
     * @param \Magento\Framework\Model\Context                     $context
     * @param \Magento\Framework\Registry                          $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory    $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory         $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                         $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                 $logger
     * @param \Magento\Framework\Module\ModuleListInterface        $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Directory\Model\CountryFactory              $countryFactory
     * @param \HpsServicesConfig                                   $config
     * @param array                                                $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \HpsServicesConfig $config,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
        // \HPS\Heartland\Model\countryFactory
        // \HPS\Heartland\Model\Payment::$_countryFactory
        $this->_countryFactory = $countryFactory;
        // \HPS\Heartland\Model\Payment::$_heartlandApi
        // \HpsServicesConfig
        $this->_heartlandApi = $config;
        // \Magento\Payment\Model\Method\AbstractMethod::getConfigData
        // \HpsServicesConfig::$secretApiKey
        $this->_heartlandApi->secretApiKey = $this->getConfigData('private_key');
        // \HpsServicesConfig::$developerId
        $this->_heartlandApi->developerId = '000000';
        // \HpsServicesConfig::$versionNumber
        $this->_heartlandApi->versionNumber = '0000';
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        return parent::order($payment, $amount); // TODO: Change the autogenerated stub
    }


    /**
     * @return \HpsCreditService
     */
    private function getHpsCreditService() {
        // \HPS\Heartland\Model\Payment::$_heartlandApi
        // \HpsCreditService::__construct
        return new \HpsCreditService($this->_heartlandApi);
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $billing
     *
     * @return \HpsCardHolder
     */
    private function getHpsCardHolder(\Magento\Sales\Model\Order\Address $billing) {
        $cardHolder = new \HpsCardHolder();

        // \Magento\Sales\Model\Order\Address::getName
        $splitName = explode(' ', $billing->getName());
        // \HpsConsumer::$firstName
        $cardHolder->firstName = $splitName[0];
        // \HpsConsumer::$lastName
        $cardHolder->lastName = $splitName[1];
        // \HpsConsumer::$address
        $cardHolder->address = $this->getHpsAddress($billing);
        // \Magento\Sales\Model\Order\Address::getTelephone
        // \HpsConsumer::$phone
        $cardHolder->phone = preg_replace('/[^0-9]/', '', $billing->getTelephone());
        return $cardHolder;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $billing
     *
     * @return \HpsAddress
     */
    private function getHpsAddress(\Magento\Sales\Model\Order\Address $billing) {
        $address = new \HpsAddress();
        $address->address = $billing->getStreetLine(1) . ' ' . $billing->getStreetLine(2);
        //\Magento\Sales\Model\Order\Address::getCity
        $address->city = $billing->getCity();
        // \Magento\Sales\Model\Order\Address::getCity
        $address->state = $billing->getRegion();
        // \Magento\Sales\Model\Order\Address::getPostcode
        $address->zip = preg_replace('/[^0-9]/', '', $billing->getPostcode());
        // \HPS\Heartland\Model\Payment::$_countryFactory
        // \Magento\Directory\Model\CountryFactory::create
        // \Magento\Directory\Model\Country::loadByCode
        // \Magento\Sales\Model\Order\Address::getCountryId
        // \Magento\Directory\Model\Country::getName
        $address->country = $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName();
        return $address;
    }

    /**
     * Performs an auth only which does not set the transaction to actually settle and charge a consumer card
     * See CreditAuth https://cert.api2.heartlandportico.com/Gateway/PorticoSOAPSchema/build/Default/webframe.html#Portico%20Schema_xsd~e-PosRequest~e-Ver1.0~e-Transaction~e-CreditAuth.html
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->log($payment->getTransactionId(), 'TID Auth Method Called: ');
        return $this->_process($payment, $amount, 'authorize');
    }

    private function getTransactionId(\Magento\Payment\Model\InfoInterface $payment, $amount){
        $transactionId = $payment->getTransactionId();
        $return = false;
        if ( $transactionId ){
            $transactionId = (explode('-', $transactionId));
            $response = $this->getHpsCreditService()->get($transactionId[0]);
            if ( $response->transactionStatus !='A'
                 || $amount > $response->authorizedAmount
                 || $response->transactionType !== \HpsTransactionType::AUTHORIZE){
                // new auth is requred
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The transaction "%1" cannot be captured. The amount is either larger than Authorized (%l) or
                    the authorisation for this transaction is no longer valid. A new authorisation is required'
                        , $transactionId
                        , $response->authorizedAmount)
                );
            }
            $return = $transactionId[0];
        }

        return $return;
    }
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {

        $this->log($payment->getTransactionId(), 'TIDt Capture Method Called: ');
        $action = 'charge';
        if ($this->getTransactionId($payment, $amount))
            $action = 'capture';
        return $this->_process($payment, $amount, $action);
    }

    /**
     * \HPS\Heartland\Model\Payment::capture this is the function that all the magic happens in
     * a transaction is constructed from the post data and the results are handled
     * called by Magento/Sales/Model/Order/Payment/Operations/CaptureOperation .php capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     *
     * @return \HPS\Heartland\Model\Payment         $this
     * @throws \Magento\Framework\Validator\Exception
     */
    private function _process(\Magento\Payment\Model\InfoInterface $payment, $amount, $action='authorise') {
        try {

            $this->log(func_num_args(), 'HPS\Heartland\Model\Payment Capture Method Called: ');
            $chargeService = $this->getHpsCreditService();
            // \HpsCardHolder
            $validCardHolder = $this->getHpsCardHolder($payment->getOrder()->getBillingAddress());
            // \HpsTransactionDetails
            $details = new \HpsTransactionDetails();
            // \HPS\Heartland\Model\Payment::$_token_value
            $suToken = $this->getToken(new \HpsTokenData); //$this->getSuToken();// this just gets the passed token value
            // \HPS\Heartland\Model\Payment::chargeToken
            switch  ($action){
                case 'authorize':
                    //authorize($amount, $currency, $cardOrToken, $cardHolder = null, $requestMultiUseToken = false, $details = null, $txnDescriptor = null, $allowPartialAuth = false, $cpcReq = false)
                    $response = $chargeService->authorize($amount,
                                                            'usd',
                                                            $suToken,
                                                            $validCardHolder,
                                                            ($this->saveMuToken() ? true : false));
                    break;
                case 'charge':
                    $response = $chargeService->charge($amount,
                                                        'usd',
                                                        $suToken,
                                                        $validCardHolder,
                                                        ($this->saveMuToken() ? true : false));
                    break;
                case 'capture':
                    //need to build out a get transaction ID method
                    //capture($transactionId, $amount = null, $gratuity = null, $clientTransactionId = null, $directMarketData = null);
                    $response = $chargeService->capture($this->getTransactionId($payment, $amount), $amount);
                    break;
                default:
                    throw new \Magento\Framework\Exception\LocalizedException($action . ' not implemented');
            }
            if (is_string($response)) {
                //throw new \Magento\Framework\Validator\Exception(__('Payment error.' . $response));
                $this->log($response, 'COULD NOT PROCESS TRANSACTION!!');
            }
            else {
                // even if the MUPT save fails the transaction should still complete so we execute this step first
                // \Magento\Payment\Model\Method\AbstractMethod::getInfoInstance
                $info = $this->getInfoInstance();
                $CcL4 = $info->getCcNumber();
                //$this->log($payment,'$payment ');

                $this->log($response, 'setStatus ');
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                @$payment->setStatus($response->responseText);
                $payment->setTransactionId($response->transactionId);
                $payment->setIsTransactionClosed(false);
                $payment->setCcLast4($CcL4);
                $payment->setAdditionalInformation($response->authorizationCode);
                $payment->setAmount($amount);
                if ($payment->isCaptureFinal($amount)) {
                    $payment->setShouldCloseParentTransaction(true);
                }
                if (isset($suToken->tokenValue)) {
                    $payment->setTransactionAdditionalInfo('token', $suToken->tokenValue);
                }/*/**/

                /*
                $payment
                    ->setTransactionId($response->transactionId)
                    ->setIsTransactionClosed(0);*/
                try {
                    if (((bool)$this->saveMuToken()) && isset($response->tokenData) && $response->tokenData->tokenValue) {
                        // \HPS\Heartland\Model\StoredCard::setStoredCards
                        HPS_STORED_CARDS::setStoredCards($response->tokenData->tokenValue, strtolower($info->getCcType()), $CcL4, $this->getAdditionalData()['cc_exp_month'], $this->getAdditionalData()['cc_exp_year']);
                    }/**/
                } catch (\Exception $e) {
                    // \Psr\Log\LoggerInterface::error
                    $this->_logger->error(__('Payment MultiUse Token: Error Unknown could not save token or one was not returned'));
                }
                // \Psr\Log\LoggerInterface::error
                $this->log($response, 'HPS\Heartland\Model\Payment Capture Method Saving MUPT Results: $response->tokenData->tokenValue ');
            }
        } catch (\Exception $e) {
            // \Magento\Payment\Model\Method\AbstractMethod::debugData
            $this->debugData(['request' => /*$requestData*/
                                  '', 'exception' => $e->getMessage()]);
            // \Psr\Log\LoggerInterface::error
            $this->_logger->error(__('Payment capturing error.'));
            // \Magento\Framework\Validator\Exception::__construct
            throw new \Magento\Framework\Validator\Exception(__('Payment error.' . $e->getMessage()));
        }
        $this->log((array)$response, 'HPS\Heartland\Model\Payment Capture Method Called: Done ');
        // \HPS\Heartland\Model\Payment
        return $this; // goes back to Magento/Sales/Model/Order/Payment/Operations/CaptureOperation.php capture function
    }

    /**
     * \HPS\Heartland\Model\Payment::chargeToken connects to the Heartland SDK to request Authorization and have
     * transaction added to the batch
     *
     * @param \HpsCreditService      $chargeService
     * @param \HpsTokenData          $suToken
     * @param \HpsCardHolder         $validCardHolder
     * @param \HpsTransactionDetails $additionalData
     * @param float                  $amount
     *
     * @return array|bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function chargeToken(\HpsCreditService $chargeService, \HpsTokenData $suToken, \HpsCardHolder $validCardHolder, \HpsTransactionDetails $additionalData, $amount) {
        $errorMsg = false;
        $response = false;
        $muToken = $this->saveMuToken() ? true : false;
        $this->log((array)func_get_args(), 'HPS\Heartland\Model\Payment  chargeToken Method Called: Arguments ');
        $this->log($muToken, 'HPS\Heartland\Model\Payment  chargeToken Method Called: $muToken ');
        try {
            $response = $chargeService->charge(
                (float)$amount,
                'usd',
                $suToken,
                $validCardHolder,
                $muToken,
                $additionalData);
        } catch (\HpsInvalidRequestException $e) {
            $errorMsg = __('Failure: ' . $e->getMessage());
        } catch (\HpsAuthenticationException $e) {
            $errorMsg = __('Failure: ' . $e->getMessage());
        } catch (\HpsGatewayException $e) {
            $errorMsg = __('Failure: ' . $e->getMessage());
        } catch (\HpsCreditException $e) {
            $errorMsg = __('Failure: ' . $e->getMessage());
        } catch (\Exception $e) {
            $errorMsg = __('Failure: ' . $e->getMessage());
        }
        if ($errorMsg) {
            $this->_logger->error(__('Payment capturing error.' . $errorMsg));
            // \Magento\Framework\Exception\LocalizedException::__construct
            //throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
            $response = $errorMsg;
        }
        $this->log($response, 'HPS\Heartland\Model\Payment chargeToken Method $response:  ');

        return $response;
    }

    /**
     * \HPS\Heartland\Model\Payment::refund tells Heartland SDK to connect to Portico and issue a refund
     * to the consumer
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return \HPS\Heartland\Model\Payment         $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->log(func_get_args(), 'HPS\Heartland\Model\Payment refund Method Called:  ');
        $transactionId = $payment->getParentTransactionId();
        $chargeService = new \HpsCreditService($this->_heartlandApi);
        // \HpsCreditService::refund
        $chargeService->refund($amount, 'usd', $transactionId);
        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);
        $this->log('', 'HPS\Heartland\Model\Payment refund Method Called:  Done');

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return \HPS\Heartland\Model\Payment         $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate() {
        // \Magento\Payment\Model\Method\AbstractMethod::getInfoInstance
        $this->log('validate', '\HPS\Heartland\Model\Payment::validate');
        $info = $this->getInfoInstance();
        $errorMsg = false;
        //\Magento\Payment\Model\Method\AbstractMethod::getConfigData
        $availableTypes = explode(',', $this->getConfigData('cctypes'));
        $ccNumber = $info->getCcNumber();
        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);
        $ccTypeConversion = array(
            'visa'       => 'VI',
            'mastercard' => 'MC',
            'amex'       => 'AE',
            'discover'   => 'DI',
            'jcb'        => 'JCB',
        );
        $this->log(strtolower($info->getCcType()), '\HPS\Heartland\Model\Payment::validate ');
        $this->log(strtolower($info->getCcType()), 'CCtype ');
        if (in_array($ccTypeConversion[ strtolower($info->getCcType()) ], $availableTypes)) {
            // \HPS\Heartland\Model\Payment::validateCcNum
            if (!$this->validateCcNum($ccNumber)) {
                $errorMsg = __('Invalid Credit Card Number.');
            }
        }
        else {
            $errorMsg = __('This credit card type is not allowed for this payment method.');
        }
        $this->log(strtolower($info->getCcType()), 'CCtypes ');
        // \HPS\Heartland\Model\Payment::getToken
        if (!$this->getToken(new \HpsTokenData)) {
            $errorMsg = __('No valid token.');
        }
        // \Magento\Payment\Model\Method\Cc::_validateExpDate
        if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = __('Please enter a valid credit card expiration date.');
        }
        if ($errorMsg) {
            // \Magento\Framework\Exception\LocalizedException::__construct
            $this->log($errorMsg, '\HPS\Heartland\Model\Payment::validate ');
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }
        $this->log('validate DONE', '\HPS\Heartland\Model\Payment::validate');

        return $this;
    }

    /**Just the last 4 digits since Heartland never sends CC to the server
     *
     * @param string $ccNumber
     *
     * @return bool
     */
    public function validateCcNum($ccNumber) { // luhn was used before but our implimentation will only validate 4 digits exist since portico will do the real validation
        return preg_match('/^[\d]{4}$/', $ccNumber) === 1;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        if (!$this->getConfigData('private_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode) {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }

        return true;
    }

    /** returns an element of associative array of data submitted via HTTP POST paymentMethod
     *
     * @return array
     */
    private function getPaymentMethod() {
        /**
         * @var array $data
         * Holds submited JSOn data in a PHP associative array
         */
        static $data = [];
        if (count($data) < 1) {
            $data = (array)HPS_Data::jsonData();
        }
        $this->log($data, 'HPS\Heartland\Model\Payment getPaymentMethod Method Called:  ');

        return $this->elementFromArray($data, 'paymentMethod');
    }

    /** returns additional_data element of paymentMethod
     *
     * @return array
     */
    private function getAdditionalData() {

        static $data = [];
        if (count($data) < 1) {
            $data = (array)$this->getPaymentMethod();
        }

        return $this->elementFromArray($data, 'additional_data');
    }

    /**
     * gets/assigns $this->_token_value from post data
     */
    private function getTokenValue() {
        static $data = [];
        if (count($data) < 1) {
            $data = (array)$this->getAdditionalData();
        }
        $r = '';
        if (key_exists('token_value', $data)) {
            $r = (string)$data['token_value'];
        }
        // ensure that the string is clean and has not leading or trailing whitespace
        $this->_token_value = (string)trim(filter_var($r, FILTER_SANITIZE_STRING));
    }

    /** evaluates if an element exists and returns it
     *
     * @param $data
     * @param $element
     *
     * @return array
     */
    private function elementFromArray($data, $element) {
        $r = [];
        if (key_exists($element, $data)) {
            $r = (array)$data[ $element ];
        }

        return $r;
    }

    /**
     * this method sets the instance  \HpsTokenData::$tokenValue
     * If the \HPS\Heartland\Model\Payment::$_token_value that is sent is an integer only then we assume it is a
     * primary key for hps_heartland_storedcard and perform a lookup
     *
     * @param \HpsTokenData $suToken
     *
     * @return \HpsTokenData
     *
     * @TODO: evaluate if something need to happen when no token is assigned. Probably safe to do nothing
     */
    private function getToken(\HpsTokenData $suToken) {
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::getToken Method initial value:  ');
        $this->getTokenValue();
        if (preg_match('/^[\w]{11,253}$/', (string)$this->_token_value) !== 1) {
            $this->_token_value = HPS_STORED_CARDS::getToken($this->_token_value);
        }
        //First identify if we have a singleuse token in memory already
        if (!$this->validateSuToken() && !$this->validateMuToken()) {
            $this->_token_value = (string)'';
        }
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::getToken Method final value:  ');

        // \HPS\Heartland\Model\Payment::$_token_value
        $suToken->tokenValue = $this->_token_value; //$this->getSuToken();// this just gets the passed token value
        return $suToken;
    }

    /**
     * saveMuToken checks the Json string from the HTTP POST to see if
     * the checkbox was checked
     *
     * @return int This is evaluated when the soap message is buiot
     * by \HpsCreditService::charge
     *
     */
    private function saveMuToken() {
        $data = $this->getAdditionalData();
        $this->_save_token_value = 0;
        if (array_key_exists('_save_token_value', $data)) {
            $this->_save_token_value = (int)$data['_save_token_value'];
        }
        $this->log((int)$this->_save_token_value, '\HPS\Heartland\Model\Payment::saveMuToken ');

        return (int)$this->_save_token_value;
    }

    /**
     * Performs regex based validation on the single-use token
     *
     * @return bool
     */
    private function validateSuToken() {
        return (bool)(preg_match('/^su[\w]{5,253}$/', (string)$this->_token_value) === 1); //supt_5EvfbSaBCj9r9HLlP3CauZ5t
    }

    /**
     * Just verifies the current token is not blank
     * multi-use tokens are always non blank strings
     * these get stored in hps_heartland_storedcard
     * by app/code/HPS/Heartland/Model/StoredCard.php
     *
     * @return bool
     */
    private function validateMuToken() {
        return (bool)(preg_match('/^[\w]{5,253}$/', (string)$this->_token_value) === 1);
    }

    /**
     * Logs to the var/log/debug.log
     * Commented out unless development is needed
     *
     * @param mixed  $param works with array or string
     * @param string $txt
     *
     * @return null
     */
    private function log($param, $txt = '') {
        try {
            getenv('MAGE_MODE') == 'developer' ? $this->_logger->log(100, $txt . print_r($param, true)) : '';
        } catch (\Exception $e) {
            $this->_logger->log(100, $txt . print_r($param, true));
        }
    }

    private function userNotification() {

    }
}
