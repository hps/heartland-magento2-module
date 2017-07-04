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

            },
            
            hpsBusy: function(){
                $("#checkout-loader-iframeEdition").fadeIn();
            },
            hpsNotBusy: function(){
                $("#checkout-loader-iframeEdition").fadeOut();
                _HPS_EnablePlaceOrder();
            },
            hpsShowCcForm: function(publicKey){
                if ( publicKey ){
                    var self = this;
                    $("#iframes").fadeIn();
                    HPS_SecureSubmit(document, Heartland, publicKey);
                    self.hpsGetCanSave();
                }

            }
            ,
            hideNewCardForm: function(){
                $("#iframes").fadeOut();
            },
            hpsGetCanSave: function(){
                var data;
                $("#saveCardCheck").parent().fadeOut();
                if(customer.isLoggedIn()) {
                    $.get("../heartland/creditcard/cansave/").success(function (data) {
                        if (data === '1') {
                            $("#saveCardCheck").parent().fadeIn();
                        } else {
                            $("#saveCardCheck").parent().fadeOut();
                        }

                    });
                }
                return data;
            },
            getCode: function () {
                return 'hps_paypal';

            },
            isActive: function () {
                return true;
            },

            /**
             * Create child message renderer component
             *
             * @returns {Component} Chainable.
             */

            getToken: function (data, event) {
                var self = this;
                if ($("#onestepcheckout-button-place-order")) {
                    $("#onestepcheckout-button-place-order").unbind("click");
                }
                self.hpsBusy();
                if ($("#securesubmit_token").val() == ''){
                    $("#bValidateButton").click();
                }else{
                    self.placeOrder();
                }
                self.hpsNotBusy();
            },
            /**
             * Place order.
             */
            isOSC: function(){
                var self = this;
                if ($("#onestepcheckout-button-place-order")){
                    $("#onestepcheckout-button-place-order").bind("click", self, function(){
                        self.getToken();
                    });
                    return false;
                }
                return true;


            },
            placeOrder: function (data, event) {

                var self = this,
                    placeOrder;
                self.hpsBusy();
                if (event) {
                    event.preventDefault();
                }
                if ($("#securesubmit_token").val() !== ''){
                    if (this.validate() && additionalValidators.validate()) {
                        this.isPlaceOrderActionAllowed(false);

                        var pData = this.getData();
                        this.getData();
                        pData.additional_data.cc_type = $('#hps_heartland_cc_type').val();
                        pData.additional_data.cc_exp_year =  $('#hps_heartland_expiration_yr').val();
                        pData.additional_data.cc_exp_month = $('#hps_heartland_expiration').val();
                        pData.additional_data.cc_number = $('#hps_heartland_cc_number').val();
                        pData.additional_data.token_value = $('#securesubmit_token').val();

                        pData.additional_data._save_token_value = (document.querySelector('#saveCardCheck').checked?1:0);
                        placeOrder = placeOrderAction(pData, false);

                        $.when(placeOrder)
                            .fail(function() {
                                self.isPlaceOrderActionAllowed(true);
                                self.hpsNotBusy();
                                $('#hps_heartland_NewCard').click();
                            })
                            .done(function() {
                                redirectOnSuccessAction.execute()
                            });

                        return true;
                    }

                    $("#iframesCardError").text("Invalid payment data.");
                    self.hpsNotBusy();
                    $('#hps_heartland_NewCard').click();

                }else{
                    $("#iframesCardError").text("Token lookup failed. Please try again.");
                    self.hpsNotBusy();
                }
                return false;
            }
        });
    }
);
