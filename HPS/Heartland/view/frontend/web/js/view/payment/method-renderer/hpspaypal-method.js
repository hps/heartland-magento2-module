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
            'ko',
            'jquery',
            'Magento_Payment/js/view/payment/cc-form',
            'Magento_Checkout/js/action/place-order',
            'Magento_Checkout/js/action/select-payment-method',
            'Magento_Checkout/js/model/quote',
            'Magento_Customer/js/model/customer',
            'Magento_Checkout/js/model/payment-service',
            'Magento_Checkout/js/checkout-data',
            'Magento_Checkout/js/model/checkout-data-resolver',
            'uiRegistry',
            'Magento_Checkout/js/model/payment/additional-validators',
            'Magento_Ui/js/model/messages',
            'uiLayout',
            'Magento_Checkout/js/action/redirect-on-success'
        ],
        function (
                ko,
                $,
                Component,
                placeOrderAction,
                selectPaymentMethodAction,
                quote,
                customer,
                paymentService,
                checkoutData,
                checkoutDataResolver,
                registry,
                additionalValidators,
                Messages,
                layout,
                redirectOnSuccessAction
                ) {
            'use strict';
            /**
             * Get the public key from HPS/Heartland/Controller/Hss/Pubkey.php as url configured based on HPS/Heartland/etc/frontend/routes.xml
             * if there is any form of error we disable the payment form
             *
             */
            return Component.extend({
                defaults: {
                    template: 'HPS_Heartland/payment/hpspaypal-form',
                    code: 'hps_paypal',
                    active: false,
                    grandTotalAmount: null

                },

                /**
                 * Set list of observable attributes
                 * @returns {exports.initObservable}
                 */
                initObservable: function () {
                    var self = this;

                    this._super()
                            .observe(['active', 'isReviewRequired', 'customerEmail']);

                    console.log('isPlaceOrderActionAllowed: ' + (this.getCode() === this.isChecked()) );

                    return this;
                },

                hpsBusy: function () {
                    $("#checkout-loader-iframeEdition").fadeIn();
                },
                hpsNotBusy: function () {
                    $("#checkout-loader-iframeEdition").fadeOut();
                    _HPS_EnablePlaceOrder();
                },                
                getCode: function () {                    
                    return this.code;

                },
                isActive: function () {
                    var active = (this.getCode() === this.isChecked());
                    return active;
                },
                
                createPaypalSession: function(){
                    //window.checkoutConfig
                    //create paypal session in windows
                    $.ajax({
                        url: "../heartland/paypal/createsession",
                        showLoader: true,
                        context: $('.payment-group'),
                        method: 'POST',
                        success: function (data) {
                            if(data !== null){
                                //console.log(data);
                                if(data.status === 'success'){
                                    $('#hps_paypal_sessionId').val(data.sessiondetails.sessionId);
                                    //window.open(data.sessiondetails.redirectUrl,'WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700');
                                    window.location.href = data.sessiondetails.redirectUrl;
                                } else if(data.status === 'error'){
                                    alert(data.message);
                                }
                            } else {
                                alert('Error in payment processing! Try again later.');
                            }                           
                        }
                    });
                },

                placeOrder: function (data, event) {
                    var self = this,
                            placeOrder;
                    self.hpsBusy();
                    if (event) {
                        event.preventDefault();
                    }
                    this.isPlaceOrderActionAllowed(false);
                    
                    
                    return false;
                }
            });
        }
);
