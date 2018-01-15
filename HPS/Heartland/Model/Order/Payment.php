<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace HPS\Heartland\Model\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Info;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;

/** Override these as Heartland wants to apply specific logic
 * Class Payment
 * @package HPS\Heartland\Model\Order
 */
class Payment extends \Magento\Sales\Model\Order\Payment
{
    private $transactionRecord = null;
    /** Can Capture
     * @return bool
     */
    
    /**
     * @var \HpsCreditService
     */
    private $hpsServicesConfig;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        ManagerInterface $transactionManager,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Processor $paymentProcessor,
        OrderRepositoryInterface $orderRepository,
        \HpsServicesConfig $hpsServicesConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $encryptor,
            $creditmemoFactory,
            $priceCurrency,
            $transactionRepository,
            $transactionManager,
            $transactionBuilder,
            $paymentProcessor,
            $orderRepository,
            null,
            null,
            $data
        );
        
        $this->hpsServicesConfig = $hpsServicesConfig;
    }

    public function canCapture()
    {
        try {
            if (preg_match(
                "/(".\Magento\Sales\Api\Data\TransactionInterface::TYPE_AUTH."|"
                           .\Magento\Sales\Api\Data\TransactionInterface::TYPE_ORDER.")$/",
                $this->getLastTransId()
            ) === 1) {
                return true;
            }
            if ($this->getHPS() === null) {
                return false;
            }
            return $this->transactionRecord->settlementAmount > 0
                ? false
                : true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
        try {
            if ($this->getHPS() === null) {
                return false;
            }
            return $this->transactionRecord->transactionStatus === 'A'
                ? true
                : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** Heartlands gatewway does not ever support Multiple partial capture but does allow for 1 only. Attempts to do
     * multiple partial captures will result in a gateway error
     * @return bool
     */
    public function canCapturePartial()
    {
        try {
            if (preg_match(
                "/(".\Magento\Sales\Api\Data\TransactionInterface::TYPE_AUTH."|"
                           .\Magento\Sales\Api\Data\TransactionInterface::TYPE_ORDER.")$/",
                $this->getLastTransId()
            ) === 1) {
                return true;
            }
            if ($this->getHPS() === null) {
                return false;
            }
            return $this->transactionRecord->settlementAmount > 0
                ? false
                : true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getHPS()
    {
        try {
            /** @var \HpsServicesConfig $hps */
            if ($this->getCcTransId() && $this->transactionRecord === null) {
                //$hps                = new \HpsServicesConfig();
                $abs                = $this->getMethodInstance();
                $this->hpsServicesConfig->secretApiKey  = $abs->getConfigData('private_key');
                $this->hpsServicesConfig->developerId   = $abs->getConfigData('developerId');
                $this->hpsServicesConfig->versionNumber = $abs->getConfigData('versionNumber');
                $creditService = new \HpsCreditService($this->hpsServicesConfig);
                $this->transactionRecord = $creditService->get($this->getCcTransId());
            }
        } catch (\Exception $e) {
            $this->transactionRecord = null;
        }
        finally{return $this->transactionRecord;
        }
    }
}
