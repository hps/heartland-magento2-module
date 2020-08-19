define([
    'jquery',
    'https://hps.github.io/token/gp-1.5.0/globalpayments.js'
], function ($, GlobalPayments) {

    GlobalPayments = GlobalPayments || window.GlobalPayments;

    var getImageURL = (function () {
        //build a relative path based on the module location on the configured server
        var scripts = document.getElementsByTagName('script');
        var element;

        for (const s in scripts) {
            if (!scripts.hasOwnProperty(s)) {
                continue;
            }
            
            if (scripts[s].src.indexOf('HPS_Heartland/js/view/payment/securesubmit') === -1) {
                continue;
            }

            element = scripts[s];
        }

        if (element !== null) {
            var myScript = element.src.split('/').slice(0, -4).join('/') + '/images/';
            //console.log(myScript);
            return function () {
                return myScript;
            };
        }
    })();

    function _HPS_addClass(element, klass)
    {
        if (element !== null && element.className.indexOf(klass) === -1) {
            element.className = element.className + ' ' + klass;
        }
    }

    function _HPS_removeClass(element, klass)
    {
        if (element !== null && element.className.indexOf(klass) === -1) {
            return;
        }
        element.className = element.className.replace(klass, '');
    }

    function setElementValue(selector, propertyToSet, valueToSet)
    {
        var element = document.querySelector(selector);
        if (element !== null) {
            switch (propertyToSet.toLowerCase()) {
                case 'value':
                    element.value = valueToSet;
                    break;
                case 'disabled':
                    element.disabled = valueToSet;
                    break;
                case 'innerhtml':
                    element.innerHTML = valueToSet;
                    break;
            }
        }
    }

    window._HPS_setHssTransaction = function _HPS_setHssTransaction(response)
    {
        setElementValue('#securesubmit_token', 'value', (response.paymentReference || response.token_value).trim());
        setElementValue('#hps_heartland_cc_number', 'value', (response.details && response.details.cardLast4 || response.last_four || '').trim());
        setElementValue('#hps_heartland_cc_type', 'value', (response.details && response.details.cardType || response.card_type || '').trim());
        setElementValue('#hps_heartland_expiration', 'value', (response.details && response.details.expiryMonth || response.exp_month || '').trim());
        setElementValue('#hps_heartland_expiration_yr', 'value', (response.details && response.details.expiryYear || response.exp_year || '').trim());
        //document.querySelector('#bPlaceOrderNow').click();
    };

    function HPS_DisablePlaceOrder()
    {
        try {
            var element = '#checkout-payment-method-load > div > div.payment-method._active > div.payment-method-content > div.actions-toolbar > div > button';
            _HPS_addClass(document.querySelector(element), 'disabled');
            setElementValue('#bPlaceOrderNow', 'disabled', true);
            setElementValue('#bPlaceOrderNow', 'disabled', 'disabled');
        } catch (e) {
        }
    }
    function HPS_EnablePlaceOrder()
    {
        try {
            var element = '#checkout-payment-method-load > div > div.payment-method._active > div.payment-method-content > div.actions-toolbar > div > button';
            _HPS_removeClass(document.querySelector(element), 'disabled');
            setElementValue('#bPlaceOrderNow', 'disabled', false);
            setElementValue('#bPlaceOrderNow', 'disabled', '');
        } catch (e) {
        }
    }
    function HPS_SecureSubmit(document, publicKey)
    {
        //if (arguments.callee.count > 0 )
        //    return;
        if (document.querySelector('#iframesCardNumber') // dont execute if this doesnt exist
                && !document.querySelector('#iframesCardNumber > iframe') //dont execute if this exists
                && publicKey) {
            //var addHandler = Heartland.Events.addHandler;
            function enablePlaceOrder(disabled)
            {
                var element = '#checkout-payment-method-load > div > div.payment-method._active > div.payment-method-content > div.actions-toolbar > div > button';
                try {
                    _HPS_removeClass(document.querySelector(element), 'disabled');
                } catch (e) {
                }
                if (!disabled) {
                    try {
                        _HPS_addClass(document.querySelector(element), 'disabled');
                    } catch (e) {
                    }
                }
            }
            enablePlaceOrder(false);


            function toAll(elements, fun)
            {
                var i = 0;
                var length = elements.length;
                for (i; i < length; i++) {
                    fun(elements[i]);
                }
            }

            function filter(elements, fun)
            {
                var i = 0;
                var length = elements.length;
                var result = [];
                for (i; i < length; i++) {
                    if (fun(elements[i]) === true) {
                        result.push(elements[i]);
                    }
                }
                return result;
            }

            function clearFields()
            {
                toAll(document.querySelectorAll('.magento2_error, .magento2-error, .magento2-message, .magento2_message'), function (element) {
                    element.remove();
                });
            }


            // Handles tokenization response
            function responseHandler(hps, response)
            {
                var getrequireval = document.querySelector('#requirecvvexp');
                var requirecvvexpval = getrequireval.value;
                var errElement = document.querySelector('#iframesCardError');

                function showError(message) {
                    _HPS_addClass(errElement, 'mage-error');
                    errElement.innerText = message;
                    document.querySelector('#iframes > input[type="submit"]').style.display = 'none';
                }

                if (response.error) {
                    showError(response.reasons.map(function (r) { return r.message + ' '; }));
                    switch (response.reasons[0].code) {
                        case 'INVALID_CARD_NUMBER':
                            hps.frames['card-number'].setFocus();
                            break;
                        case 'INVALID_CARD_SECURITY_CODE':
                            hps.frames['card-cvv'].setFocus();
                            break;
                        case 'INVALID_CARD_EXPIRATION':
                            hps.frames['card-expiration'].setFocus();
                            break;
                    }
                    return;
                }

                if (!response.paymentReference) {
                    showError('Card number is invalid');
                    hps.frames['card-number'].setFocus();
                    return;
                }

                if (!response.details.expiryMonth || !response.details.expiryYear) {
                    showError('Invalid Expiration Date.');
                    hps.frames['card-expiration'].setFocus();
                    return;
                }

                if (requirecvvexpval == 'yes' && response.details.cardSecurityCode == false) {
                    showError('Invalid CVV.');
                    hps.frames['card-cvv'].setFocus();
                    return;
                }

                if (document.querySelector('#iframesCardNumber > iframe') != null) {
                    toAll(document.querySelectorAll('#iframesCardNumber > iframe, #iframesCardExpiration > iframe, #iframesCardCvv > iframe'), function (element) {
                        try {
                            element.remove();
                        } catch (e) {
                        }
                    });
                    document.querySelector('#iframes > input[type="submit"]').style.display = 'none';

                    try {
                        _HPS_addClass(document.querySelector('#iframesCardCvvLabel > span'), 'hideMe');
                    } catch (e) {
                    }

                    var errElement = document.querySelector('#iframesCardError');
                    if (errElement) {
                        errElement.innerText = '';
                    }

                    try {
                        _HPS_removeClass(errElement, 'mage-error');
                    } catch (e) {
                    }

                    window._HPS_setHssTransaction(response);
                    document.querySelector("#bPlaceOrderNow").click();
                }
            }

            // Load function to attach event handlers when WC refreshes payment fields
            window.securesubmitLoadEvents = function () {
                if (!GlobalPayments) {
                    return;
                }

                toAll(document.querySelectorAll('.card-number, .card-cvc, .expiry-date'), function (element) {
                    addHandler(element, 'change', clearFields);
                });

                toAll(document.querySelectorAll('.saved-selector'), function (element) {
                    addHandler(element, 'click', function (e) {
                        var display = 'none';
                        if (document.getElementById('secure_submit_card_new').checked) {
                            display = 'block';
                        }
                        toAll(document.querySelectorAll('.new-card-content'), function (el) {
                            el.style.display = display;
                        });

                        // Set active flag
                        toAll(document.querySelectorAll('.saved-card'), function (el) {
                            _HPS_removeClass(el, 'active');
                        });
                        _HPS_addClass(element.parentNode.parentNode, 'active');
                    });
                });

                if (document.querySelector('.securesubmit_new_card .card-number')) {
                    Heartland.Card.attachNumberEvents('.securesubmit_new_card .card-number');
                    Heartland.Card.attachExpirationEvents('.securesubmit_new_card .expiry-date');
                    Heartland.Card.attachCvvEvents('.securesubmit_new_card .card-cvc');
                }
            };
            window.securesubmitLoadEvents();

            GlobalPayments.configure({
                publicApiKey: publicKey,
            });
            // Create a new `HPS` object with the necessary configuration
            var hps = GlobalPayments.ui.form({
                fields: {
                    'card-number': {
                        target: '#iframesCardNumber',
                        placeholder: '•••• •••• •••• ••••',
                        label: 'Card Number Input'
                    },
                    'card-expiration': {
                        target: '#iframesCardExpiration',
                        placeholder: 'MM / YYYY',
                        label: 'Card Expiration Input'
                    },
                    'card-cvv': {
                        target: '#iframesCardCvv',
                        placeholder: 'CVV',
                        label: 'Card Security Code Input'
                    }
                },
                // Collection of CSS to inject into the iframes.
                // These properties can match the site's styles
                // to create a seamless experience.

                styles: {
                    'input': {
                        'background': '#fff',
                        'border': '1px solid #666',
                        'border-color': '#bbb3b9 #c7c1c6 #c7c1c6',
                        'box-sizing': 'border-box',
                        'font-family': 'Arial, Helvetica Neue, Helvetica, sans-serif',
                        'font-size': '18px !important',
                        'line-height': '18px !important',
                        'margin': '0 .5em 0 0',
                        'max-width': '100%',
                        'outline': '0',
                        'padding': '15px 13px 13px 13px',
                        'vertical-align': 'middle',
                        'width': '100%'
                    },
                    '#secure-payment-field-body': {
                        'width': '100%'
                    },
                    '#secure-payment-field-wrapper': {
                        'position': 'relative'
                    },
                    // Card Number
                    '#secure-payment-field[name="cardNumber"] + .extra-div-1': {
                        'display': 'block',
                        'width': '56px',
                        'height': '44px',
                        'position': 'absolute',
                        'top': '4px',
                        'right': '10px',
                        'background-position': 'bottom',
                        'background-repeat': 'no-repeat',
                        'background-size': '56px auto'
                    },
                    '#secure-payment-field[name="cardNumber"].valid + .extra-div-1': {
                        'background-position': 'top'
                    },
                    '#secure-payment-field.card-type-visa + .extra-div-1': {
                        'background-image': 'url("' + getImageURL() + 'ss-inputcard-visa@2x.png")'
                    },
                    '#secure-payment-field.card-type-jcb + .extra-div-1': {
                        'background-image': 'url("' + getImageURL() + 'ss-inputcard-jcb@2x.png")'
                    },
                    '#secure-payment-field.card-type-discover + .extra-div-1': {
                        'background-image': 'url("' + getImageURL() + 'ss-inputcard-discover@2x.png")'
                    },
                    '#secure-payment-field.card-type-amex + .extra-div-1': {
                        'background-image': 'url("' + getImageURL() + 'ss-inputcard-amex@2x.png")'
                    },
                    '#secure-payment-field.card-type-mastercard + .extra-div-1': {
                        'background-image': 'url("' + getImageURL() + 'ss-inputcard-mastercard@2x.png")'
                    },
                    '@media only screen and (max-width : 290px)': {
                        '#secure-payment-field[name="cardNumber"] + .extra-div-1': {
                            'display': 'none'
                        }
                    },
                    // Card CVV
                    '#secure-payment-field[name="cardCvv"] + .extra-div-1': {
                        'display': 'block',
                        'width': '59px',
                        'height': '39px',
                        'background-image': 'url("' + getImageURL() + 'ss-cvv@2x.png")',
                        'background-size': '59px auto',
                        'background-position': 'top',
                        'position': 'absolute',
                        'top': '6px',
                        'right': '7px'
                    }
                }
            });

            hps.on('token-success', function(r) { responseHandler(hps, r); });
            hps.on('token-error', function(r) { responseHandler(hps, r); });
            hps.on('error', function(r) { responseHandler(hps, r); });

            // Attach a handler to interrupt the form submission
            $('#iframes').submit(function (e) {

                // Prevent the form from continuing to the `action` address
                e.preventDefault();

                // manually include submit button
                var fields = ['submit'];
                var target = hps.frames['card-number'];

                for (var type in hps.frames) {
                    if (hps.frames.hasOwnProperty(type)) {
                        fields.push(type);
                    }
                }

                for (var type in hps.frames) {
                    if (!hps.frames.hasOwnProperty(type)) {
                        continue;
                    }

                    var frame = hps.frames[type];

                    if (!frame) {
                        continue;
                    }

                    GlobalPayments.internal.postMessage.post({
                        data: {
                            fields: fields,
                            target: target.id,
                        },
                        id: frame.id,
                        type: "ui:iframe-field:request-data",
                    }, frame.id);
                }
            });
        }
    };

    return {
        HPS_SecureSubmit: HPS_SecureSubmit,
        HPS_DisablePlaceOrder: HPS_DisablePlaceOrder,
        HPS_EnablePlaceOrder: HPS_EnablePlaceOrder,
    };
});
