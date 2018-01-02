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
define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function ($, Class, alert, domObserver) {
    'use strict';

    return Class.extend({

        defaults: {
            $selector: null,
            selector: 'edit_form',
            container: 'payment_form_hps_heartland',
            active: false,
            scriptLoaded: false,
            hps_heartland: null,
            selectedCardType: null,
            publicKey: null,
            imports: {
                onActiveChange: 'active'
            }
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            var self = this;
            self.$selector = $('#' + self.selector);
            this._super()
                    .observe([
                        'active',
                        'scriptLoaded',
                        'selectedCardType'
                    ]);

            // re-init payment method events
            self.$selector.off('changePaymentMethod.' + this.code)
                    .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));

            // listen block changes
            domObserver.get('#' + self.container, function () {
                self.$selector.off('submit');
            });

            return this;
        },

        /**
         * Call HPS Tokenization
         */
        initHpsToken: function () { 
            var self = this;
            try {
                $('body').trigger('processStart');
                hps.Messages.post(
                    {
                            accumulateData: true,
                            action: 'tokenize',
                            message: this.publicKey, //'pkapi_cert_jKc1FtuyAydZhZfbB3',
                        },
                    'cardNumber'
                );
            } catch (e) {
                $('body').trigger('processStop');
                self.error(e.message);
            }
        },

        /**
         * Enable/disable current payment method
         * @param {Object} event
         * @param {String} method
         * @returns {exports.changePaymentMethod}
         */
        changePaymentMethod: function (event, method) {
            this.active(method === this.code);

            return this;
        },

        /**
         * Triggered when payment changed
         * @param {Boolean} isActive
         */
        onActiveChange: function (isActive) {
            if (!isActive) {
                this.$selector.off('submitOrder.' + this.code);
                return;
            }
            this.disableEventListeners();
            window.order.addExcludedPaymentMethod(this.code);

            this.enableEventListeners();
        },

        /**
         * Show alert message
         * @param {String} message
         */
        error: function (message) {
            alert({
                content: message
            });
        },

        /**
         * Enable form event listeners
         */
        enableEventListeners: function () {
            this.$selector.on('submitOrder.hps_heartland', this.submitOrder.bind(this));
        },

        /**
         * Disable form event listeners
         */
        disableEventListeners: function () {
            this.$selector.off('submitOrder');
            this.$selector.off('submit');
        },

        /**
         * Trigger order submit
         */
        submitOrder: function (e) { 
            if ($('#securesubmit_token').val() !== '') {
                this.placeOrder();
                return true;
            }
            //generate a new Token and submit order
            this.$selector.trigger('afterValidate.beforeSubmit');
            $('body').trigger('processStop');
            this.initHpsToken();
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        },

        /**
         * Place order
         */
        placeOrder: function () { 
            $('#' + this.selector).trigger('realOrder');
        }
    });
});
