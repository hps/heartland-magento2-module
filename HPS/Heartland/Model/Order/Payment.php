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
class Payment
    extends \Magento\Sales\Model\Order\Payment {
    private $_transactionRecord = null;
    /** Can Capture
     * @return bool
     */

    public
    function canCapture()
    { //TODO: ensure that this is an authorization but the gateway will throw an error if this fails for now
        try {

            if ($this->getHPS() === null) {
                return false;

            }
            return $this->_transactionRecord->settlementAmount > 0
                ? false
                : true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public
    function canVoid()
    {
        try {

            if ($this->getHPS() === null) {
                return false;

            }
            return $this->_transactionRecord->transactionStatus === 'A'
                ? true
                : false;
        }
        catch (\Exception $e) {
            return false;
        }

    }

    /** Heartlands gatewway does not ever support Multiple partial capture but does allow for 1 only. Attempts to do
     * multiple partial captures will result in a gateway error
     * @return bool
     */
    public
    function canCapturePartial()
    {
        try {
            if (preg_match("/{Transaction::TYPE_AUTH}$/", $this->getLastTransId()) === 1)
                return true;
            if ($this->getHPS() === null) {
                return false;

            }
            return $this->_transactionRecord->settlementAmount > 0
                ? false
                : true;
        }
        catch (\Exception $e) {
            return false;
        }

    }

    private
    function getHPS()
    {
        try {
            /** @var \HpsServicesConfig $hps */
            if ($this->getCcTransId() && $this->_transactionRecord === null) {
                $hps                = \HPS\Heartland\Helper\ObjectManager::getObjectManager()->get('\HpsServicesConfig');
                $abs                = $this->getMethodInstance();
                $hps->secretApiKey  = $abs->getConfigData('private_key');
                $hps->developerId   = $abs->getConfigData('developerId');
                $hps->versionNumber = $abs->getConfigData('versionNumber');
                $creditService = new \HpsCreditService($hps);
                $this->_transactionRecord = $creditService->get($this->getCcTransId());

            }
        }
        catch (\Exception $e) {
            $this->_transactionRecord = null;
        }
        finally{return $this->_transactionRecord;}
    }
}
