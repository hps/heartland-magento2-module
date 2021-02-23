/*
 *  Heartland payment method model
 *
 *  @category    HPS
 *  @package     HPS_Heartland
 *  @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 *  @copyright   Heartland (http://heartland.us)
 *  @license     https://github.com/hps/heartland-magento2-module/blob/master/LICENSE.md
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
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'HPS_Heartland/js/view/payment/securesubmit'
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
        redirectOnSuccessAction,
        url,
        HPS_Heartland
    ) {
        'use strict';
        /**
         * Get the public key from HPS/Heartland/Controller/Hss/Pubkey.php as url configured based on HPS/Heartland/etc/frontend/routes.xml
         * if there is any form of error we disable the payment form
         *
         */
        return Component.extend({
            defaults: {
                template: 'HPS_Heartland/payment/heartland-form'

            },
            messages: new Messages(),
            hpsSavedCards: function () {
                var self = this;
                $("#SavedCardsTable").fadeIn();

                if ($("#SavedCardsTable tr").length < 2) {
                    self.hpsBusy();
                    if (!customer.isLoggedIn()) {
                        $("#SavedCardsTable").append($("<tr />"));
                        self.hpsNewCard();
                        return;
                    }

                    $.ajax({
                        url: url.build('heartland/creditcard/get'),
                        showLoader: true,
                        context: $('#SavedCardsTable'),
                        success: function (data) {
                            if (typeof data === 'string') {
                              data = JSON.parse(data);
                            }

                            if (data.length !== 0) {
                                $("#iframes").fadeOut();

                                // process json string to table rows
                                self.drawTable(data);
                                self.hpsNotBusy();

                                $("#iframes").fadeOut();
                            } else {
                                $("#SavedCardsTable").append($("<tr />"));
                                self.hpsNewCard();
                            }
                            $("#hps_heartland_NewCard").insertAfter($("#SavedCardsTable tr").last());
                        }
                    });
                }
            },
            drawTable: function (data) {
                var self = this;
                for (var i = 0; i < data.length; i++) {
                    self.drawRow(data[i]);
                }
            },

            drawRow: function (rowData) {
                var rOnClick = "onclick='var response" + rowData.token_value + " = {token_value:\"" + rowData.token_value + "\", last_four:\"" + rowData.cc_last4 + "\", card_type:\"" + rowData.cc_type + "\", exp_month:\"" + rowData.cc_exp_month + "\", exp_year:\"" + rowData.cc_exp_year + "\"};document.querySelector(\"#hssCardSelected" + rowData.token_value + "\").checked=true;require([\"jquery\"],function($){$(\"#iframes\").fadeOut();});;_HPS_setHssTransaction(response" + rowData.token_value + ");' title=\"Pay with this card\"";
                var row = $("<tr " + rOnClick + " />");
                $("#SavedCardsTable").append(row); //this will append tr element to table... keep its reference for a while since we will add cels into it
                // {"token_value":"1","cc_last4":"1111","cc_type":"visa","cc_exp_month":"02","cc_exp_year":"2021"}
                row.append($("<td width=\"width:100px\" ><input style=\"width:100px;cursor:pointer;\" type=\"radio\" name=\"HPSTokens[]\" id=\"hssCardSelected" + rowData.token_value + "\"></td>"));
                row.append($("<th align=\"left\" id=\"image_holder_" + rowData.token_value + "\"  >" + rowData.cc_type.toUpperCase() + " ending in " + rowData.cc_last4 + "<br />Expiring on " + rowData.cc_exp_month + "/" + rowData.cc_exp_year + "<span class=\"card-type-" + rowData.cc_type + "\" /></th>"));
            },





            hpsNewCard: function () {
                var self = this;
                $('#securesubmit_token').removeAttr('value');;
                $("#SelectNewCardHPS").prop('checked', true);
                self.hpsBusy();
                if ($("#SavedCardsTable tr").length > 1) {
                    $.get(url.build('heartland/api/pubkey')) // as url configured based on HPS/Heartland/etc/frontend/routes.xml
                        .success(function (publicKey) {
                            self.hpsShowCcForm(publicKey);
                            self.hpsNotBusy();
                        }).fail(function () {
                        $('#hps_heartland').parent().parent().querySelector('span').innerHTML = ' <font color=red>Please contact site owner</font>';
                        self.hpsNotBusy();
                    });
                }
                return true;
            },
            hpsBusy: function () {
                $("#checkout-loader-iframeEdition").fadeIn();
            },
            hpsNotBusy: function () {
                $("#checkout-loader-iframeEdition").fadeOut();
                HPS_Heartland.HPS_EnablePlaceOrder();
            },
            hpsShowCcForm: function (publicKey) {
                if (publicKey ) {
                    var self = this;
                    $("#iframes").fadeIn();
                    HPS_Heartland.HPS_SecureSubmit(document, publicKey);
                    self.hpsGetCanSave();
                }

            }
            ,
            hideNewCardForm: function () {
                $("#iframes").fadeOut();
            },
            hpsGetCanSave: function () {
                var data;
                $("#saveCardCheck").parent().fadeOut();
                if (customer.isLoggedIn()) {
                    $.get(url.build('heartland/creditcard/cansave')).success(function (data) {
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
                return 'hps_heartland';

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
                /**
                * For amasty comaptablity issue
                */
                if($('div.am-checkout').length) {
                    additionalValidators.validate();
                }
                self.hpsBusy();
                if ($("#securesubmit_token").val() == '') {
                    $("#bValidateButton").click();
                } else {
                    self.placeOrder();
                }
                self.hpsNotBusy();
            },
            /**
             * Place order.
             */
            isOSC: function () {
                var self = this;
                if ($("#onestepcheckout-button-place-order")) {
                    $("#onestepcheckout-button-place-order").bind("click", self, function () {
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
                if ($("#securesubmit_token").val() !== '') {
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
                            .fail(function () {
                                self.isPlaceOrderActionAllowed(true);
                                self.hpsNotBusy();
                                $('#hps_heartland_NewCard').click();
                            })
                            .done(function () {
                                redirectOnSuccessAction.execute()
                            });

                        return true;
                    }

                    $("#iframesCardError").text("Invalid payment data.");
                    self.hpsNotBusy();
                    $('#hps_heartland_NewCard').click();
                } else {
                    $("#iframesCardError").text("Token lookup failed. Please try again.");
                    self.hpsNotBusy();
                }
                return false;
            },
            Requirecvvexp: function () {
                 var valueElement = document.querySelector('#requirecvvexp');
                $.get(url.build('heartland/creditcard/requirecvvexpconfig')).success(function (data) {
                   $("#requirecvvexp").val(data);
                });
            }
        });
    }
);
