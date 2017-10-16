/* @api */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'NaitusEirl_PagoFacil/payment/form'
        },
        /**
         * Get payment method user message
         */
        getDescription: function () {
            return window.checkoutConfig.payment.pagofacil.description;
        },
        afterPlaceOrder: function () {
            window.location.href = window.checkoutConfig.payment.pagofacil.redirectUrl;
        }
    });
});
