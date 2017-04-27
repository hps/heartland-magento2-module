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


namespace HPS\Heartland\Model;

use \HPS\Heartland\Helper\ObjectManager as HPS_OM;
use \HPS\Heartland\Model\StoredCard as HPS_STORED_CARDS;
use \HPS\Heartland\Helper\Data as HPS_DATA;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Phrase;
use \Magento\Sales\Api\Data\TransactionInterface as Transaction;

/**
 * Class Payment
 * \HPS\Heartland\Model\Payment
 * @method \Magento\Payment\Model\Method\AbstractMethod getConfigData($field, $storeId = null)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 *
 * @package HPS\Heartland\Model
 */
class Payment
    extends \Magento\Payment\Model\Method\Cc {
    /**
     *
     */
    const CODE = 'hps_heartland';
    /**
     * @var bool
     */
    public $_token_value = false;
    /** Maps the HPS transaction type indicators to the Magento word strings
     * @array $transactionTypeMap
     */
    protected $transactionTypeMap
        = [\HpsTransactionType::AUTHORIZE => Transaction::TYPE_AUTH,
           \HpsTransactionType::CAPTURE   => Transaction::TYPE_ORDER,
           \HpsTransactionType::CHARGE    => Transaction::TYPE_CAPTURE,
           \HpsTransactionType::REFUND    => Transaction::TYPE_REFUND,
           \HpsTransactionType::REVERSE   => Transaction::TYPE_REFUND,
           \HpsTransactionType::VOID      => Transaction::TYPE_VOID,];
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_code = self::CODE;
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
    protected $_canOrder  = true;
    protected $_canCancel = true;

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
     * @var float
     */
    protected $_minAmount = 0.01;
    /**
     * @var array
     */
    protected $_debugReplacePrivateDataKeys
        = ['number',
           'exp_month',
           'exp_year',
           'cvc'];
    /**
     * @var bool
     */
    protected $_context = false;
    /**
     * @var array
     */
    protected $_heartlandConfigFields
        = ['developerId'   => '002914',
           'versionNumber' => '1573',];

    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    private $messageManager = null;
    private $_objectManager = null;

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
    public
    function __construct(\Magento\Framework\Model\Context $context,
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
                         array $data = [])
    {
        parent::__construct($context,
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
                            $data);
        // \HPS\Heartland\Model\countryFactory
        // \HPS\Heartland\Model\Payment::$_countryFactory
        $this->_countryFactory = $countryFactory;
        // \HPS\Heartland\Model\Payment::$_heartlandApi
        // \HpsServicesConfig
        $this->_heartlandApi = $config;
        // \HpsServicesConfig::$secretApiKey
        $this->_heartlandApi->secretApiKey = $this->getConfigData('private_key');
        // \HpsServicesConfig::$developerId
        $this->_heartlandApi->developerId = $this->_heartlandConfigFields['developerId'];
        // \HpsServicesConfig::$versionNumber
        $this->_heartlandApi->versionNumber = $this->_heartlandConfigFields['versionNumber'];
        $this->_objectManager               = HPS_OM::getObjectManager();
        $this->messageManager
                                            = $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface');;
    }
    //public
    /**
     * Performs an auth only which does not set the transaction to actually settle and charge a consumer card
     * See \HpsCreditService::authorize
     * called by \Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation::authorize
     *
     * @param \Magento\Sales\Model\Order\Payment\Interceptor|\Magento\Payment\Model\InfoInterface $payment
     * @param float                                                                               $amount
     *
     * @api
     * @return \HPS\Heartland\Model\Payment        $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public
    function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->_payment($payment, $amount, \HpsTransactionType::AUTHORIZE);
    }

    /**
     * Potentially authorize and capture \HpsCreditService::charge or just capture  \HpsCreditService::reverse to
     * potentially reduce any hold on the card over the amount of the capture and then \CreditService::capture
     * called by \Magento\Sales\Model\Order\Payment\Operations\CaptureOperation::capture
     *
     * @param \Magento\Sales\Model\Order\Payment\Interceptor|\Magento\Payment\Model\InfoInterface $payment
     * @param float                                                                               $amount
     *
     * @api
     * @return \HPS\Heartland\Model\Payment        $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    //\Magento\Sales\Model\Order\Payment::canCapture
    public
    function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->_payment($payment, $amount, \HpsTransactionType::CHARGE);
    }

    public
    function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->_payment($payment, $amount, \HpsTransactionType::$CAPTURE);
    }

    public
    function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->_payment($payment, null, \HpsTransactionType::VOID);
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
    public
    function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->_payment($payment, $amount, \HpsTransactionType::REFUND);
    }


    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public
    function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigData('private_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Validate payment method information object
     *
     * @return \HPS\Heartland\Model\Payment         $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public
    function validate()
    {
        $this->log('validate', '\HPS\Heartland\Model\Payment::validate ');
        $info           = $this->getInfoInstance();
        $errorMsg       = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));
        $ccNumber       = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);
        /*$ccTypeConversion = ['visa'       => 'VI',
                             'mastercard' => 'MC',
                             'amex'       => 'AE',
                             'discover'   => 'DI',
                             'jcb'        => 'JCB'];
        if (strtolower($info->getCcType()) === '') {
            return false;
        }
        $this->log('[' . strtolower($info->getCcType()) . ']', '\HPS\Heartland\Model\Payment::validate ');
        $this->log($availableTypes, '\HPS\Heartland\Model\Payment::validate $availableTypes ');
        $this->log($ccTypeConversion, '\HPS\Heartland\Model\Payment::validate $ccTypeConversion ');
        $this->log(strtolower($info->getCcType()), 'CCtypes ');
        if (in_array($ccTypeConversion[ strtolower($info->getCcType()) ], $availableTypes)) {
            $this->log($ccTypeConversion[ strtolower($info->getCcType()) ], 'CCtype ');
            // \HPS\Heartland\Model\Payment::validateCcNum
            if (!$this->validateCcNum($ccNumber)) {
                $errorMsg = __('Invalid Credit Card Number.');
            }
        }
        else {
            $errorMsg = __('This credit card type is not allowed for this payment method.');
        }*/
        // \HPS\Heartland\Model\Payment::getToken
        if (!$this->getToken(new \HpsTokenData)) {
            $errorMsg = __('No valid token.');
        }
        // \Magento\Payment\Model\Method\Cc::_validateExpDate
        if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = __('Please enter a valid credit card expiration date. ');
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
    public
    function validateCcNum($ccNumber)
    { // luhn was used before but our implimentation will only validate 4 digits exist since portico will do the real validation
        return preg_match('/^[\d]{4}$/', $ccNumber) === 1;
    }

    /**
     * @return \HpsCreditService
     */
    private
    function getHpsCreditService()
    {
        // \HPS\Heartland\Model\Payment::$_heartlandApi
        // \HpsCreditService::__construct
        return new \HpsCreditService($this->_heartlandApi);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Sales\Model\Order\Address|null $billing
     *
     * @return \HpsCardHolder
     */
    private
    function getHpsCardHolder(\Magento\Sales\Api\Data\OrderAddressInterface $billing)
    {

        $cardHolder = new \HpsCardHolder();
        // \Magento\Sales\Model\Order\Address::getName
        //$splitName = explode(' ', $billing->getName());
        // \HpsConsumer::$firstName
        $cardHolder->firstName = $this->__sanitize($billing->getFirstname());
        // \HpsConsumer::$lastName
        $cardHolder->lastName = $this->__sanitize($billing->getLastname());
        // \HpsConsumer::$address
        $cardHolder->address = $this->getHpsAddress($billing);
        // \Magento\Sales\Model\Order\Address::getTelephone
        // \HpsConsumer::$phone
        $cardHolder->phone = \HpsInputValidation::cleanPhoneNumber($billing->getTelephone());

        $cardHolder->email = trim(filter_var($billing->getEmail(), FILTER_SANITIZE_EMAIL));

        return $cardHolder;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address|\Magento\Sales\Api\Data\OrderAddressInterface|null $billing
     *
     * @return \HpsAddress
     */
    private
    function getHpsAddress(\Magento\Sales\Api\Data\OrderAddressInterface $billing)
    {
        $address = new \HpsAddress();
        // \Magento\Sales\Model\Order\Address::getStreetLine
        /** @var \Magento\Sales\Model\Order\Address|\Magento\Sales\Api\Data\OrderAddressInterface|null $billing
         * @method  \Magento\Sales\Model\Order\Address getStreetLine($number) */
        $address->address
            = $this->__sanitize(implode(' ', $billing->getStreet()));
        // \Magento\Sales\Model\Order\Address::getCity
        $address->city = $this->__sanitize($billing->getCity());
        // \Magento\Sales\Model\Order\Address::getCity
        $address->state = $this->__sanitize($billing->getRegion());
        // \Magento\Sales\Model\Order\Address::getPostcode
        $address->zip = \HpsInputValidation::cleanZipCode($billing->getPostcode());
        // \HPS\Heartland\Model\Payment::$_countryFactory
        // \Magento\Directory\Model\CountryFactory::create
        // \Magento\Directory\Model\Country::loadByCode
        // \Magento\Sales\Model\Order\Address::getCountryId
        // \Magento\Directory\Model\Country::getName
        $address->country = $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName();

        return $address;
    }


    /**
     * \HPS\Heartland\Model\Payment::_process this is the function that all the magic happens in
     * a transaction is constructed from the post data and the results are handled
     * the caller
     *
     * @param \Magento\Sales\Model\Order\Payment\Interceptor|\Magento\Payment\Model\InfoInterface $payment
     * @param float                                                                               $requestedAmount
     *
     * @param \HpsTransactionType|int                                                             $paymentAction
     *
     * @return \HPS\Heartland\Model\Payment        $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private
    function _payment(\Magento\Payment\Model\InfoInterface $payment,
                      $requestedAmount = 0.00,
                      $paymentAction
                      = \HpsTransactionType::CHARGE)
    {

        // Sanitize

        /**
         * @var  \HpsCreditCard|\HpsTokenData|int                                                        $parentPaymentID
         * @var \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order\Address                $order
         * @var \HpsCreditService                                                                        $chargeService
         * @var array                                                                                    $errorMsg
         * @var \HpsCardHolder|null                                                                      $validCardHolder
         * @var \HpsReportTransactionDetails|null                                                        $reportTxnDetail
         * @var \HpsReversal|\HpsReversal|\HpsRefund|\HpsAuthorization|\HpsReportTransactionDetails|null $response
         * @var null|\HpsTransactionDetails                                                              $details
         * @var int                                                                                      $paymentAction
         * @var string                                                                                   $currency
         * @var null|float                                                                               $newAuthAmount
         *
         */
        $storeName       = substr(trim(filter_var(HPS_OM::getObjectManager()
                                                        ->get('\Magento\Store\Model\StoreManagerInterface')
                                                        ->getStore()
                                                        ->getName()),
                                       FILTER_SANITIZE_SPECIAL_CHARS),
                                  0,
                                  18);
        $errorMsg        = [];
        $successMsg      = [];
        $noticeMsg       = [];
        $validCardHolder = null;
        $reportTxnDetail = null;
        $response        = null;
        $details         = null;
        $newAuthAmount   = null;
        $suToken         = null;  
        
        try {


            $chargeService = $this->getHpsCreditService();
            $currency      = HPS_DATA::getCurrencyCode();
            /** $parentPaymentID While this could also be \HpsCreditCard|\HpsTokenData in this case we are retrieving the
             * transaction
             * ID */
            //list($parentPaymentID) = explode('-', $payment->getParentTransactionId());
            $parentPaymentID = (int) $payment->getCcTransId();
            $canSaveToken    = $this->saveMuToken()
                ? true
                : false;

            $this->log(func_num_args(), 'HPS\Heartland\Model\Payment Capture Method Called: ');

            /*
             * The below logic serves to determine if we need to authorise and capture or just add the transaction to
             *  the batch
             */

            if ($parentPaymentID && is_integer($parentPaymentID)) {
                $reportTxnDetail = $chargeService->get($parentPaymentID);
                if ($paymentAction === \HpsTransactionType::CHARGE) {

                    if ($reportTxnDetail->transactionStatus != 'A'
                        || $requestedAmount > $reportTxnDetail->authorizedAmount
                        || $reportTxnDetail->transactionType !== \HpsTransactionType::AUTHORIZE
                    ) {
                        // new auth is requred
                        throw new \Magento\Framework\Exception\LocalizedException(__('The transaction "%1" cannot be
                        captured. The amount is either larger than Authorized (%2) or
                    the authorisation for this transaction is no longer valid. A new authorisation is required',
                                                                                     $parentPaymentID,
                                                                                     $reportTxnDetail->authorizedAmount));
                    } // validated acceptable authorization
                    // set to do a capture
                    $paymentAction = \HpsTransactionType::CAPTURE;
                    $this->log($paymentAction, 'paymentAction in _payment method changed to capture: ');
                }
                elseif ($paymentAction === \HpsTransactionType::REFUND && $reportTxnDetail->transactionStatus == 'A') {                    
                    //perform the reversal when transactionStatus is Active
                    $paymentAction = \HpsTransactionType::REVERSE;
                }
            }// end of verifying that we have something that looks like  transaction ID to use
            // these are the only 2 transaction types where Magento2 does not need a transaction ID to reference
            elseif ($paymentAction !== \HpsTransactionType::AUTHORIZE && $paymentAction !== \HpsTransactionType::CHARGE) {
                //We know we dont have a valid transaction id so its time to throw an error

            }// all of these types of transactions require a transaction id from  previous transaction


            /*
             * \HpsTransactionType::CAPTURE does not accept cardholder or token so there is no need to create these
             * objects
             */
            if ($paymentAction === \HpsTransactionType::AUTHORIZE
                || $paymentAction === \HpsTransactionType::CAPTURE
                || $paymentAction === \HpsTransactionType::CHARGE
                || $paymentAction === \HpsTransactionType::REFUND
            ) {
                $order = $payment->getOrder();
                // \HpsCardHolder
                $validCardHolder = $this->getHpsCardHolder($order->getBillingAddress());

                $this->log($paymentAction, 'HPS\Heartland\Model\Payment $paymentAction: ');
                if ($paymentAction === \HpsTransactionType::AUTHORIZE || $paymentAction === \HpsTransactionType::CHARGE) {
                    $this->log($suToken, 'HPS\Heartland\Model\Payment getToken Method Called: ');
                    // \HPS\Heartland\Model\Payment::$_token_value

                    $suToken
                        = $this->getToken(new \HpsTokenData,
                                          $order->getCustomerId()); //$this->getSuToken();// this just gets the passed
                    // token value

                    $this->log($suToken, 'HPS\Heartland\Model\Payment after getToken Method Called: ');
                }
            }

            /*
             * execute the portic messages related to the specified action
             */
            switch ($paymentAction) {
                /*
                 * \HpsTransactionType::AUTHORIZE places a hold on the card and requests an approval from the Card
                 * Issuer
                 * This transaction will not be settled and the hold will fall off of the card holders account
                 * usually after 7 days but this time frame is up to the issuer and may vary.
                 * If the transaction is later captured it is then transfered from the card holders account to the
                 * merchant.
                 * Approval codes are typically only good for 30 days or so
                 *
                 * the typical use case for this transaction is if a product is ordered and not immediately shipped
                 */
                case (\HpsTransactionType::AUTHORIZE): // Portico CreditAuth \HpsTransactionType::AUTHORIZE

                    $this->log($suToken, 'HPS\Heartland\Model\Payment authorize Method Called: ');
                    /** @var \HpsAuthorization $response Properties found in the HpsAuthorization */
                    ;
                    $response = $chargeService->authorize(\HpsInputValidation::checkAmount($requestedAmount),
                                                          $currency,
                                                          $suToken,
                                                          $validCardHolder,
                        ($this->validateSuToken() ? true : false),
                                                          null,
                                                          $storeName);
                    $this->log($response, 'HPS\Heartland\Model\Payment authorize Method response: ');
                    
                    if (isset($response->tokenData) && $response->tokenData->tokenValue){
                        $payment->setCcNumberEnc($response->tokenData->tokenValue);
                    }
                    if(!$this->validateSuToken()){
                        $payment->setCcNumberEnc($suToken->tokenValue);
                    }
                    break;
                /*
                 * This transaction is the compliment to \HpsTransactionType::AUTHORIZE.
                 * It is the necesary follow up transaction initiated from the invoice function of magento
                 * administrative pages.
                 * NOTE::: Only one capture is supported  on an authorisation. by our gateway. a new authorization
                 * will be necessary for follow up partial captures
                 */
                case (\HpsTransactionType::CAPTURE): // Portico CreditAddtoBatch \HpsTransactionType::CAPTURE                    
                    $this->log($suToken, 'HPS\Heartland\Model\Payment capture Method Called: ');
                    $response = $chargeService->capture($parentPaymentID, $requestedAmount);

                    /*
                     * at this stage if additional captures are needed  new authorizations are required
                     *
                     *
                     */
                    break;
                /*
                 * This transaction will request an approval code from the issuer and then add it to the daily
                 * settlement. No further action is required in order for the merchant to aquire funds.
                 * Digital media sales which are immediately delivered are an ideal use case for this transaction
                 */
                case (\HpsTransactionType::CHARGE): // Portico CreditSale \HpsTransactionType::CHARGE
                    $this->log($suToken, 'HPS\Heartland\Model\Payment charge Method Called: ');
                    $response = $chargeService->charge(\HpsInputValidation::checkAmount($requestedAmount),
                                                       HPS_DATA::getCurrencyCode(),
                                                       $suToken,
                                                       $validCardHolder,
                                                       $canSaveToken);

                    $payment->setParentTransactionId($response->transactionId . '-' . $this->transactionTypeMap[ $paymentAction ]);
                    break;
                /**
                 * Reverses the full amount and removes any related capture from the batch*/
                case (\HpsTransactionType::VOID): // Portico CreditVoid \HpsTransactionType::VOID
                    $response = $chargeService->void($parentPaymentID);
                    break;
                case (\HpsTransactionType::REVERSE):// Portico CreditReversal \HpsTransactionType::REVERSE
                    $newAuthAmount = $reportTxnDetail->settlementAmount - $requestedAmount;
                    $response      = $chargeService->reverse($parentPaymentID,
                                                             $reportTxnDetail->authorizedAmount,
                                                             $currency,
                                                             $details,
                                                             $newAuthAmount);
                    break;
                case (\HpsTransactionType::REFUND):// Portico CreditReturn \HpsTransactionType::REFUND
                    $response = $chargeService->refund($requestedAmount,
                                                       $currency,
                                                       $parentPaymentID,
                                                       $validCardHolder,
                                                       $details);
                    break;
                default:
                    throw new LocalizedException(new Phrase(__($paymentAction . ' not implemented')));
            }
            // even if the MUPT save fails the transaction should still complete so we execute this step first

            /**
             * @var \Magento\Payment\Model\InfoInterface|\Magento\Payment\Model\Method\AbstractMethod|\Magento\Framework\DataObject $info
             * @method string $info::getCcNumber() Retrieves the value in the Credit card field in this instance
             * @method string getCcType() Retrieves the text type for the credit card in this instance
             */
            $info = $this->getInfoInstance();
            $CcL4 = $info->getCcNumber();;

            $this->log($response, 'setStatus ');
            // set items always found in the response header
            /** @var \HpsTransaction $response Properties found in the header */
            //$payment->setStatus($response->responseText);
            $payment->setTransactionId($response->transactionId . '-' . $this->transactionTypeMap[ $paymentAction ]);
            $payment->setAdditionalInformation(serialize($response));
            if ($payment->isCaptureFinal($requestedAmount)) {
                $payment->setShouldCloseParentTransaction(true);
            }
            
            // token saving should just work but just in case we dont want to stop the transaction if it didnt
            try {
                if (((bool) $canSaveToken) && isset($response->tokenData) && $response->tokenData->tokenValue) {
                    /**This call will automatically make sure the expire date is updated on a save*/
                    $chargeService->updateTokenExpiration($response->tokenData->tokenValue,
                                                          $this->getAdditionalData()['cc_exp_month'],
                                                          $this->getAdditionalData()['cc_exp_year']);
                    // \HPS\Heartland\Model\StoredCard::setStoredCards
                    HPS_STORED_CARDS::setStoredCards($response->tokenData->tokenValue,
                                                     strtolower($info->getCcType()),
                                                     $CcL4,
                                                     $this->getAdditionalData()['cc_exp_month'],
                                                     $this->getAdditionalData()['cc_exp_year'],
                                                     $order->getData('customer_id'));
                    $successMsg[] = __("Payment token saved for future purchases");
                }/**/
            }
            catch (\Exception $e) {
                // \Psr\Log\LoggerInterface::error
                $this->_logger->error(__('Payment MultiUse Token: Error Unknown could not save token or one was
                    not returned. The most likely cause would be that Multi-use tokens need to be enabled by
                    Heartland - %1',
                                         $e->getMessage()));
                $noticeMsg[] = __('We could not save your payment information for later use.');
            }
            // \Psr\Log\LoggerInterface::error
            // an error any where here will it seems not get picked up by Magento2 error handlers.

            $this->log($response,
                       'HPS\Heartland\Model\Payment Capture Method Saving MUPT Results: $response->tokenData->tokenValue ');


            $this->log((array) $response, 'HPS\Heartland\Model\Payment _process Method Called: Done ');

            switch (get_class($response)) {

                case 'HpsReversal':
                    /** @var \HpsReversal $response Properties found in the HpsReversal */
                    $successMsg[] = __("The amount authorised for Transaction ID: %1 for
                        [\$%2] was reduced to [\$%3] successfully",
                                       $payment->getCcTransId(),
                                       $reportTxnDetail->settlementAmount,
                                       $requestedAmount);
                    break;

                case 'HpsRefund':
                    /** @var \HpsRefund $response Properties found in the HpsRefund */
                    $successMsg[] = __("The Transaction ID: %1 was refunded for \$%2
                        successfully",
                                       $payment->getCcTransId(),
                                       $requestedAmount);
                    $payment->setBaseAmountRefunded($requestedAmount);

                    break;

                case 'HpsVoid':
                    /** @var \HpsVoid $response Properties found in the HpsVoid */
                    $successMsg[] = __("The Transaction ID: %1 was voided successfully", $payment->getCcTransId());
                    break;

                case 'HpsAuthorization':
                    /** @var \HpsAuthorization $response Properties found in the HpsAuthorization */
                    $payment->setCcTransId($response->transactionId);
                    $payment->setCcApproval($response->authorizationCode);
                    $payment->setCcAvsStatus($response->avsResultCode . ': ' . $response->avsResultText);
                    $payment->setCcCidStatus($response->cvvResultCode . ': ' . $response->cvvResultText);
                    $payment->setCcLast4($this->getAdditionalData()['cc_number']);
                    $payment->setCcExpMonth($this->getAdditionalData()['cc_exp_month']);
                    $payment->setCcExpYear($this->getAdditionalData()['cc_exp_year']);
                    $payment->setCcType($this->getAdditionalData()['cc_type']);
                    $payment->setCcOwner($validCardHolder->lastName . ', ' . $validCardHolder->firstName);
                    $actionVerb = 'Authorised for';
                    if ($paymentAction === \HpsTransactionType::CHARGE) {
                        $actionVerb = 'Charged';
                        /** @var \HpsReportTransactionDetails $detail Properties found in the as a result of capture or get */
                        $detail = $chargeService->get($response->transactionId);
                        $payment->setAmountPaid($detail->settlementAmount);
                    }
                    //Build a message to show the user what is happening
                    $successMsg[]
                        = __("The %1 ending in %2 which expires on: %3 \\ %4 was %5 \$%6 successfully.",
                             $response->cardType,
                             $CcL4,
                             $this->getAdditionalData() ['cc_exp_month'],
                             $this->getAdditionalData()['cc_exp_year'],
                             $actionVerb,
                             $requestedAmount);

                    break;

                case 'HpsReportTransactionDetails':
                    /** @var \HpsReportTransactionDetails $response Properties found in the HpsReportTransactionDetails */
                    $payment->setAmountPaid($response->settlementAmount);
                    $payment->setParentTransactionId($parentPaymentID . '-' . $this->transactionTypeMap[ $paymentAction ]);
                    $successMsg[] = __("The %1 ending in %2 was Invoiced successfully \$%3",
                                       $response->cardType,
                                       substr($response->maskedCardNumber, -4),
                                       $response->settlementAmount);

                    break;

                default:
                    break;
            }

        }
        catch (\HpsInvalidRequestException $e) {
            $errorMsg[] = 'Incorrect parameters on line: ' . $e->getLine() . '. Please get your log files and contact Heartland:
            ' . $e->getMessage();
        }
        catch (\HpsAuthenticationException $e) {
            $errorMsg[]
                = 'Authentication on line: ' . $e->getLine() . '. Failure: Credentials Rejected by Gateway please
                contact Heartland: ' . $e->getMessage();
        }
        catch (\HpsGatewayException $e) {

            $errorMsg[] = 'Gateway Error: ' . $e->getMessage();

        }
        catch (\HpsCreditException $e) {
            $errorMsg[] = 'Cannot process Payment: ' . $e->getMessage();
        }
        catch (\HpsException $e) {
            $errorMsg[]
                = 'General Error on line: ' . $e->getLine() . '. The problem will require troubleshooting: ' . $e->getMessage();
        }
        catch (\Exception $e) {

            $errorMsg[]
                = $e->getMessage();
        }
        finally { // trying to prevent Magento2 from incorrectly finishing a transaction that has an error
            // send any error messages from processing to the browser
            if (count($errorMsg) || ! property_exists($response, 'transactionId') || ! ($response->transactionId > 0
                ) ) {

                        $errorMsg[]
                            = 'Please contact this retailer to complete your transaction';
                //throw new LocalizedException(new Phrase(print_r($errorMsg,true) . " Your transaction could not be
                //completed!"));
            }


            if (count($errorMsg) && property_exists($response, 'transactionId') && ($response->transactionId > 0
                ) ) {

                if (($paymentAction === \HpsTransactionType::CHARGE
                     || $paymentAction === \HpsTransactionType::AUTHORIZE)
                    && ($response->transactionId > 0)
                ) {
                    //Reverse any auth
                    try{

                        $chargeService = $this->getHpsCreditService();
                        $chargeService->reverse($response->transactionId,
                                                $requestedAmount,
                                                $currency);
                        unset($successMsg);
                        $successMsg[] = 'Your transaction was reversed and will not be charged.';
                    }catch (\Exception $e) {
                        $errorMsg[]
                            = $e->getMessage();
                        $errorMsg[]
                            = 'Please contact this retailer to complete your transaction';   }
                }
                //throw new LocalizedException(new Phrase(print_r($errorMsg,true) . " Your transaction could not be
                //completed!"));
            }
            if (count($successMsg)) {
                foreach ($successMsg as $msg) {
                    if (trim($msg)) {
                        $this->messageManager->addSuccessMessage($msg);
                    }
                }
            }

            if (count($noticeMsg)) {
                foreach ($noticeMsg as $msg) {
                    if (trim($msg)) {
                        $this->messageManager->addNoticeMessage($msg);
                    }
                }
            }
            if (count($errorMsg)) {
                foreach ($errorMsg as $msg) {
                    if (trim($msg)) {
                        $this->messageManager->addErrorMessage($msg);
                    }
                }
                throw new LocalizedException(new Phrase($errorMsg . " Your transaction could not be completed!"));
            }
        }

        return $this; // goes back to


    }

    /**
     * saveMuToken checks the JSON string from the HTTP POST to see if
     * the checkbox was checked
     *
     * @return int This is evaluated when the soap message is built
     * by \HpsCreditService::charge
     *
     */
    private
    function saveMuToken()
    {
        $data                    = $this->getAdditionalData();
        $this->_save_token_value = 0;
        if (array_key_exists('_save_token_value', $data)) {
            $this->_save_token_value = (int) $data['_save_token_value'];
        }
        $this->log($this->_save_token_value, '\HPS\Heartland\Model\Payment::saveMuToken ');

        return $this->_save_token_value;
    }

    /** returns additional_data element of paymentMethod
     *
     * @return array
     */
    private
    function getAdditionalData()
    {

        static $data = [];
        if (count($data) < 1) {
            $data = (array) $this->getPaymentMethod();
        }

        return $this->elementFromArray($data, 'additional_data');
    }

    /** returns an element of associative array of data submitted via HTTP POST paymentMethod
     *
     * @return array
     * */
    private
    function getPaymentMethod()
    {
        /**
         * @var array $data
         * Holds submitted JSOn data in a PHP associative array
         */
        static $data = [];
        if (count($data) < 1) {
            $data = (array) HPS_Data::jsonData();
        }
        $this->log($data, 'HPS\Heartland\Model\Payment getPaymentMethod Method Called:  ');

        return $this->elementFromArray($data, 'paymentMethod');
    }

    /** evaluates if an element exists and returns it
     *
     * @param $data
     * @param $element
     *
     * @return array
     */
    private
    function elementFromArray($data, $element)
    {
        $r = [];
        if (key_exists($element, $data)) {
            $r = (array) $data[ $element ];
        }

        return $r;
    }

    /**
     * Takes anything presented strips begining and ending whitespace and returns only string with no special characters
     *
     * @param $data
     *
     * @return string
     */
    private
    function __sanitize($data)
    {
        return trim(filter_var($data, FILTER_SANITIZE_STRING));
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
    private
    function getToken(\HpsTokenData $suToken, $custID = null)
    {
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::getToken Method initial value:  ');
        $this->getTokenValue();
        //if token value is an number it's may be a stored card need to check with heartland_storedcard_id value
        if (!empty($this->_token_value) && is_numeric($this->_token_value) && HPS_STORED_CARDS::getCanStoreCards()) {            
            $this->_token_value = HPS_STORED_CARDS::getToken($this->_token_value, $custID);
        }
       
        if (empty($this->_token_value)) {
            $this->_token_value = (string) '';
        }
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::getToken Method final value:  ');

        // \HPS\Heartland\Model\Payment::$_token_value
        $suToken->tokenValue = $this->_token_value; //$this->getSuToken();// this just gets the passed token value
        return $suToken;
    }

    /**
     * gets/assigns $this->_token_value from post data
     */
    private
    function getTokenValue()
    {
        static $data = [];
        if (count($data) < 1) {
            $data = (array) $this->getAdditionalData();
        }
        $this->log($data, '\HPS\Heartland\Model\Payment::getTokenValue data:  ');
        
        $r = '';        
        if (key_exists('token_value', $data)) {
            $r = (string) $data['token_value'];
        }
        // ensure that the string is clean and has not leading or trailing whitespace
        $this->_token_value = (string) trim(filter_var($r, FILTER_SANITIZE_STRING));
    }

    /**
     * Performs regex based validation on the single-use token
     *
     * @return bool
     */
    private function validateSuToken()
    {
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::validateSuToken preg:  ');
        return (!empty($this->_token_value));
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
    private function log($param, $txt = '')
    {
        try {
            getenv('MAGE_MODE') == 'developer'
                ? $this->_logger->log(100, $txt . print_r($param, true))
                : '';
        }
        catch (\Exception $e) {
            $this->_logger->log(100, $txt . print_r($param, true));
        }
        $this->_logger->log(100, $txt . print_r($param, true));
    }

}
