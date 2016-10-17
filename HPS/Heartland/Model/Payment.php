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


namespace HPS\Heartland\Model;

use \HPS\Heartland\Model\StoredCard as HPS_STORED_CARDS;
use \HPS\Heartland\Helper\Data as HPS_DATA;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Phrase;
use \Magento\Sales\Api\Data\TransactionInterface as Transaction;

/**
 * Class Payment
 * \HPS\Heartland\Model\Payment
 *
 * @method \Magento\Payment\Model\Method\AbstractMethod getConfigData($field, $storeId = null)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
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
           \HpsTransactionType::REVERSE   => Transaction::TYPE_VOID,
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
        = ['developerId'   => '000000',
           'versionNumber' => '0000',];

    /** Process funds back to the consumer. this is the opposit of what \HPS\Heartland\Model\Payment::_payment does
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param null|float                           $amount
     * @param null|float                           $newAmount
     */
    private
    function _return(\Magento\Payment\Model\InfoInterface $payment, $amount = null, $newAmount = null)
    {
        /**
         * @var  \HpsCreditCard|\HpsTokenData|int                                                   $cardData
         * @var \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order\Address           $order
         * @var \Magento\Sales\Model\Order\Payment\Interceptor|\Magento\Payment\Model\InfoInterface $payment
         * @method \Magento\Sales\Model\Order\Payment\Interceptor getTransactionId()
         * @method \Magento\Sales\Model\Order\Payment\Interceptor getOrder()
         * @method \Magento\Sales\Api\Data\OrderInterface getBillingAddress()
         * @var \HpsCreditService                                                                   $chargeService
         * @var string                                                                              $errorMsg
         * @var \HpsCardHolder|null                                                                 $validCardHolder
         * @var \HpsReportTransactionDetails|null                                                   $authResponse
         * @var \HpsReversal|\HpsReversal|\HpsRefund|null                                           $response
         * @var null|\HpsTransactionDetails                                                         $details
         * @var int                                                                                 $action
         * @var string                                                                              $currency
         * @var null|float                                                                          $authAmount
         *
         */
        $chargeService   = $this->getHpsCreditService();
        $errorMsg        = false;
        $validCardHolder = null;
        $authResponse    = null;
        $response        = null;
        $details         = null;
        $currency        = HPS_DATA::getCurrencyCode();
        $authAmount      = null;
        $cardData        = $payment->getParentTransactionId();

        try {
            switch ($action) {
                case (\HpsTransactionType::VOID):
                    $response = $chargeService->void($cardData);
                    break;
                case (\HpsTransactionType::REVERSE):
                    $response = $chargeService->reverse($cardData, $amount, $currency, $details, $authAmount);
                    break;
                case (\HpsTransactionType::REFUND):
                    $order           = $payment->getOrder();
                    $validCardHolder = $this->getHpsCardHolder($order->getBillingAddress());
                    $response        = $chargeService->refund($amount,
                                                              $currency,
                                                              $cardData,
                                                              $validCardHolder,
                                                              $details);
                    break;
                default:
                    $errorMsg
                        = 'An error occured. ' . __FILE__ . ':' . __LINE__ . ' There is no method for action: ' . $action . '. not implemented';

            }

            $transactionId = $payment->getParentTransactionId();
            $chargeService = $this->getHpsCreditService();
            // \HpsCreditService::refund
            $chargeService->refund($amount, 'usd', $transactionId);
            $payment->setTransactionId($transactionId . '-' . Transaction::TYPE_REFUND);
            $payment->setParentTransactionId($transactionId);
            $payment->setIsTransactionClosed(1);
            $payment->setShouldCloseParentTransaction(1);
        }
        catch (\HpsInvalidRequestException $e) {
            $errorMsg = 'Incorrect parameters on line: ' . $e->getLine() . '. Please get your log files and contact Heartland:
            ' . $e->getMessage();
        }
        catch (\HpsAuthenticationException $e) {
            $errorMsg
                = 'Authentication on line: ' . $e->getLine() . '. Failure: Credentials Rejected by Gateway please
                contact Heartland: ' . $e->getMessage();
        }
        catch (\HpsGatewayException $e) {
            $errorMsg = 'Incorrect parameters on line: ' . $e->getLine() . '. Please
                contact Heartland:  ' . $e->getMessage();
        }
        catch (\HpsException $e) {
            $errorMsg
                = 'General Error on line: ' . $e->getLine() . '. The problem will require troubleshooting: ' . $e->getMessage();
        }
        if ($errorMsg) {
            throw new LocalizedException(new Phrase(__($errorMsg)));
        }

        return $response;
    }

    private
    function porticoTransaction()
    {

    }

    private
    function userNotification()
    {

    }

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
        $this->log($payment->getTransactionId(), 'TID Auth Method Called: ');

        return $this->_payment($payment, $amount, \HpsTransactionType::AUTHORIZE);
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
    private
    function log($param, $txt = '')
    {
        try {
            getenv('MAGE_MODE') == 'developer'
                ? $this->_logger->log(100, $txt . print_r($param, true))
                : '';
        }
        catch (\Exception $e) {
            $this->_logger->log(100, $txt . print_r($param, true));
        }
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
        $requestedAmount = \HpsInputValidation::checkAmount($requestedAmount);
        /**
         * @var  \HpsCreditCard|\HpsTokenData|int                                         $parentPaymentID
         * @var \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order\Address $order
         * @var \HpsCreditService                                                         $chargeService
         * @var string                                                                    $errorMsg
         * @var \HpsCardHolder|null                                                       $validCardHolder
         * @var \HpsReportTransactionDetails|null                                         $reportTxnDetail
         * @var \HpsReversal|\HpsReversal|\HpsRefund|null                                 $response
         * @var null|\HpsTransactionDetails                                               $details
         * @var int                                                                       $paymentAction
         * @var string                                                                    $currency
         * @var null|float                                                                $newAuthAmount
         *
         */
        try {


            $chargeService   = $this->getHpsCreditService();
            $errorMsg        = false;
            $validCardHolder = null;
            $reportTxnDetail = null;
            $response        = null;
            $details         = null;
            $currency        = HPS_DATA::getCurrencyCode();
            $newAuthAmount   = null;
            /** $parentPaymentID While this could also be \HpsCreditCard|\HpsTokenData in this case we are retrieving the
             * transaction
             * ID */
            $parentPaymentID = $payment->getParentTransactionId();
            $suToken         = null;
            $validCardHolder = null;
            $reportTxnDetail = null;
            $response        = null;
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
                        throw new \Magento\Framework\Exception\LocalizedException(__('The transaction "%1" cannot be captured. The amount is either larger than Authorized (%s) or
                    the authorisation for this transaction is no longer valid. A new authorisation is required',
                                                                                     $parentPaymentID,
                                                                                     $reportTxnDetail->authorizedAmount));
                    } // validated acceptable authorization
                    // set to do a capture
                    $paymentAction = \HpsTransactionType::CAPTURE;
                }
                elseif ($paymentAction === \HpsTransactionType::VOID) {

                    if ($reportTxnDetail->transactionStatus != 'A'
                        || $requestedAmount > $reportTxnDetail->settlementAmount
                    ) {
                        // new auth is requred
                        throw new \Magento\Framework\Exception\LocalizedException(__('The transaction "%1" cannot be
                        Voided. The amount is either larger than Authorized ("%s") or the authorisation for this
                        transaction is no longer Active. ',
                                                                                     $parentPaymentID,
                                                                                     $reportTxnDetail->authorizedAmount));
                    } // validated acceptable authorization

                    // refunds are only appropriate if the transaction is no longer active
                    // fortunately if we want to return less than was authorized we can simply reduce the amount
                    if ($requestedAmount < $reportTxnDetail->settlementAmount) {
                        $paymentAction = \HpsTransactionType::REVERSE;
                    }
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
                || $paymentAction === \HpsTransactionType::REFUND
            ) {
                $order = $payment->getOrder();
                // \HpsCardHolder
                $validCardHolder = $this->getHpsCardHolder($order->getBillingAddress());
                if ($paymentAction !== \HpsTransactionType::REFUND) {
                    // \HPS\Heartland\Model\Payment::$_token_value
                    $suToken
                        = $this->getToken(new \HpsTokenData); //$this->getSuToken();// this just gets the passed token value
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
                    $response = $chargeService->authorize(\HpsInputValidation::checkAmount($requestedAmount),
                                                          $currency,
                                                          $suToken,
                                                          $validCardHolder,
                                                          $canSaveToken);
                    break;
                /*
                 * This transaction is the compliment to \HpsTransactionType::AUTHORIZE.
                 * It is the necesary follow up transaction initiated from the invoice function of magento
                 * administrative pages.
                 * NOTE::: Only one capture is supported  on an authorisation. by our gateway. a new authorization
                 * will be necesary for follow up partial captures
                 */
                case (\HpsTransactionType::CAPTURE): // Portico CreditAddtoBatch \HpsTransactionType::CAPTURE
                    /*
                     * reduce the \HpsAuthorization::$authorizedAmount HpsReversal if \HpsAuthorization::$authorizedAmount is greator than \HPS\Heartland\Model\amount
                     *
                     */
                    try {
                        if (\HpsInputValidation::checkAmount($reportTxnDetail->authorizedAmount) > \HpsInputValidation::checkAmount($requestedAmount)) {
                            $chargeService->reverse($parentPaymentID,
                                                    \HpsInputValidation::checkAmount($reportTxnDetail->authorizedAmount),
                                                    HPS_DATA::getCurrencyCode(),
                                                    null,
                                                    $requestedAmount);
                        }
                    }
                    catch (\Exception $e) {
                        $this->log($e->getCode(),
                                   'Reversal error. Logged only the capture proceeded normally
                        unless an error was generated by that call: ' . $e->getMessage());

                    }
                    /*
                     * Capture the sale
                     */
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
                    $response = $chargeService->charge(\HpsInputValidation::checkAmount($requestedAmount),
                                                       HPS_DATA::getCurrencyCode(),
                                                       $suToken,
                                                       $validCardHolder,
                                                       $canSaveToken);
                    break;
                /**
                 * Reverses the full amount and removes any related capture from the batch*/
                case (\HpsTransactionType::VOID): // Portico CreditVoid \HpsTransactionType::VOID
                    $response = $chargeService->void($parentPaymentID);
                    break;
                case (\HpsTransactionType::REVERSE):// Portico CreditReversal \HpsTransactionType::REVERSE
                    $newAuthAmount = $reportTxnDetail->settlementAmount - $requestedAmount;
                    $response      = $chargeService->reverse($parentPaymentID,
                                                             $requestedAmount,
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

            // \Magento\Payment\Model\Method\AbstractMethod::getInfoInstance
            $info = $this->getInfoInstance();
            // magic method \Magento\Framework\DataObject::__call
            $CcL4 = $info->getCcNumber();
            //$this->log($payment,'$payment ');

            $this->log($response, 'setStatus ');
            // magic method \Magento\Framework\DataObject::__call
            @$payment->setStatus($response->responseText);
            $payment->setTransactionId($response->transactionId);
            $payment->setIsTransactionClosed(false);
            $payment->setCcLast4($CcL4);
            $payment->setAdditionalInformation($response->authorizationCode);

            // magic method \Magento\Framework\DataObject::__call

            if ($payment->isCaptureFinal($requestedAmount)) {
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
                if (((bool) $canSaveToken) && isset($response->tokenData) && $response->tokenData->tokenValue) {
                    // \HPS\Heartland\Model\StoredCard::setStoredCards
                    HPS_STORED_CARDS::setStoredCards($response->tokenData->tokenValue,
                                                     strtolower($info->getCcType()),
                                                     $CcL4,
                                                     $this->getAdditionalData()['cc_exp_month'],
                                                     $this->getAdditionalData()['cc_exp_year']);
                }/**/
            }
            catch (\Exception $e) {
                // \Psr\Log\LoggerInterface::error
                $this->_logger->error(__('Payment MultiUse Token: Error Unknown could not save token or one was
                    not returned. The most likely cause would be that Multi-use tokens need to be enabled by
                    Heartland'));
            }
            // \Psr\Log\LoggerInterface::error
            $this->log($response,
                       'HPS\Heartland\Model\Payment Capture Method Saving MUPT Results: $response->tokenData->tokenValue ');
        }
        catch (\HpsInvalidRequestException $e) {
            $errorMsg = __('Failure: The message structure sent to Heartland was invalid. Please check log files for
            more detail before contacting Heartland: ' . $e->getMessage());
        }
        catch (\HpsAuthenticationException $e) {
            $errorMsg = __('Failure: Credentials Rejected by Gateway please contact Heartland: ' . $e->getMessage());
        }
        catch (\HpsGatewayException $e) {
            $errorMsg = __('Failure: An Error occured at Heartlands gateway. If this problem continues please
            contact Heartland: ' . $e->getMessage());
        }
        catch (\HpsCreditException $e) {
            $errorMsg = __('Failure: The payment method was rejected. The response that follows comes from the
            CardHolders Issueing bank. Please direct your consumer to their bank for more info: ' . $e->getMessage());
        }
        catch (\Exception $e) {
            $errorMsg
                = __('Failure: General code error. Additional troubleshooting may be required: ' . $e->getMessage());
        }
        if ($errorMsg) {
            // \Psr\Log\LoggerInterface::error
            $this->_logger->error($response,
                                  'HPS\Heartland\Model\Payment Capture Method Saving MUPT Results: $response->tokenData->tokenValue ');
            throw new LocalizedException(new Phrase(__('Payment failure. Please contact site owner to
            complete this transaction')));
        }
        $this->log((array) $response, 'HPS\Heartland\Model\Payment _process Method Called: Done ');

        // \HPS\Heartland\Model\Payment
        return $this; // goes back to
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
     * saveMuToken checks the Json string from the HTTP POST to see if
     * the checkbox was checked
     *
     * @return int This is evaluated when the soap message is buiot
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
        $this->log((int) $this->_save_token_value, '\HPS\Heartland\Model\Payment::saveMuToken ');

        return (int) $this->_save_token_value;
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
     */
    private
    function getPaymentMethod()
    {
        /**
         * @var array $data
         * Holds submited JSOn data in a PHP associative array
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
     * Takes anything presented strips begining and ending whitespace and returns only string with no special characters
     *
     * @param $data
     *
     * @return string
     */
    private
    function __sanitize($data)
    {
        return trim(filter_var(@print_r($data, true), FILTER_SANITIZE_STRING));
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
    function getToken(\HpsTokenData $suToken)
    {
        $this->log($this->_token_value, '\HPS\Heartland\Model\Payment::getToken Method initial value:  ');
        $this->getTokenValue();
        if (preg_match('/^[\w]{11,253}$/', (string) $this->_token_value) !== 1) {
            $this->_token_value = HPS_STORED_CARDS::getToken($this->_token_value);
        }
        //First identify if we have a singleuse token in memory already
        if (!$this->validateSuToken() && !$this->validateMuToken()) {
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
    private
    function validateSuToken()
    {
        return (bool) (preg_match('/^su[\w]{5,253}$/',
                                  (string) $this->_token_value) === 1); //supt_5EvfbSaBCj9r9HLlP3CauZ5t
    }

    /**
     * Just verifies the current token is not blank
     * multi-use tokens are always non blank strings
     * these get stored in hps_heartland_storedcard
     * by app/code/HPS/Heartland/Model/StoredCard.php
     *
     * @return bool
     */
    private
    function validateMuToken()
    {
        return (bool) (preg_match('/^[\w]{5,253}$/', (string) $this->_token_value) === 1);
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public
    function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }

        return true;
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
    public
    function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->log($payment->getTransactionId(), 'TransactionID lookup Capture Method Called: ');

        return $this->_payment($payment, $amount, \HpsTransactionType::CHARGE);
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
        $this->log(func_get_args(), 'HPS\Heartland\Model\Payment refund Method Called:  ');
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
    public
    function validate()
    {
        // \Magento\Payment\Model\Method\AbstractMethod::getInfoInstance
        $this->log('validate', '\HPS\Heartland\Model\Payment::validate');
        $info     = $this->getInfoInstance();
        $errorMsg = false;
        //\Magento\Payment\Model\Method\AbstractMethod::getConfigData
        $availableTypes = explode(',', $this->getConfigData('cctypes'));
        $ccNumber       = $info->getCcNumber();
        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);
        $ccTypeConversion = ['visa'       => 'VI',
                             'mastercard' => 'MC',
                             'amex'       => 'AE',
                             'discover'   => 'DI',
                             'jcb'        => 'JCB',];
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
    public
    function validateCcNum($ccNumber)
    { // luhn was used before but our implimentation will only validate 4 digits exist since portico will do the real validation
        return preg_match('/^[\d]{4}$/', $ccNumber) === 1;
    }

    public
    function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        return parent::void($payment); // TODO: Change the autogenerated stub
    }

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
    }
}
