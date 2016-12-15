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
        return true;
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
    public function cancel(){
        return false;
    }
}
