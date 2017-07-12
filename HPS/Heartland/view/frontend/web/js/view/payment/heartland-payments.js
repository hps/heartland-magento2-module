/*
 *  Heartland payment method model
 *
 *  @category    HPS
 *  @package     HPS_Heartland
 *  @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 *  @copyright   Heartland (http://heartland.us)
 *  @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */
/*browser:true*/
/*global define*/
define(
        [
            'uiComponent',
            'Magento_Checkout/js/model/payment/renderer-list'
        ],
        function (
                Component,
                rendererList
                ) {
            'use strict';

            var config = window.checkoutConfig.payment,
                    hpsPaymentMethod = 'hps_heartland',
                    hpsPaypalMethod = 'hps_paypal';


            rendererList.push(
                    {
                        type: 'hps_heartland',
                        component: 'HPS_Heartland/js/view/payment/method-renderer/heartland-method'
                    },
                    {
                        type: 'hps_paypal',
                        component: 'HPS_Heartland/js/view/payment/method-renderer/hpspaypal-method'
                    }
            );

            /** Add view logic here if needed */
            return Component.extend({});

        }
);