<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace HPS\Heartland\Model\Order;

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
        \HpsServicesConfig $hpsServicesConfig
    ) {
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
                $hps                = new \HpsServicesConfig();
                $abs                = $this->getMethodInstance();
                $hps->secretApiKey  = $abs->getConfigData('private_key');
                $hps->developerId   = $abs->getConfigData('developerId');
                $hps->versionNumber = $abs->getConfigData('versionNumber');
                $creditService = new \HpsCreditService($hps);
                $this->transactionRecord = $creditService->get($this->getCcTransId());
            }
        } catch (\Exception $e) {
            $this->transactionRecord = null;
        }
        finally{return $this->transactionRecord;
        }
    }
}
