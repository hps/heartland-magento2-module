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
    /** Can Capture
     * @return bool
     */
    public
    function canCapture()
    { //TODO: ensure that this is an authorization but the gateway will throw an error if this fails for now
        ;
       $l =$this->getLastTransId();
        $d = $this->getHPS()->get($this->getCcTransId());
        return $d->transactionStatus === 'A'?true:false;
    }

    /**
     * @return bool
     */
    public
    function canVoid()
    {
        return false; //true;
    }

    /** Heartlands gatewway does not ever support Multiple partial capture but does allow for 1 only. Attempts to do
     * multiple partial captures will result in a gateway error
     * @return bool
     */
    public
    function canCapturePartial()
    {
        return true;
    }
    private function getHPS(){
        /** @var \HpsServicesConfig $hps */
        $hps = \HPS\Heartland\Helper\ObjectManager::getObjectManager()->get('\HpsServicesConfig');
        $abs = $this->getMethodInstance();
        $hps->secretApiKey = $abs->getConfigData('private_key');
        $hps->developerId = $abs->getConfigData('developerId');
        $hps->versionNumber = $abs->getConfigData('versionNumber');
        return new \HpsCreditService($hps);
    }
}
