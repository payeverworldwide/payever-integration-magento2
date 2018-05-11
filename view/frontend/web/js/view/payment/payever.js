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
        rendererList.push(

            {
                type: 'payever_stripe',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_paymill_directdebit',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_sofort',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_paymill_creditcard',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_installment',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_installment_no',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_installment_dk',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_installment_se',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_invoice_no',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_paypal',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_santander_invoice_de',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_payex_faktura',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            },
            {
                type: 'payever_payex_creditcard',
                component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            }
            /*{
            type: 'payever_stripe',
            component: 'Payever_Payever/js/view/payment/method-renderer/payever-method'
            } */
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
