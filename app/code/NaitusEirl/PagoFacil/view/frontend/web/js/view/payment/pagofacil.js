/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'pagofacil',
            component: 'NaitusEirl_PagoFacil/js/view/payment/method-renderer/pagofacil'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
