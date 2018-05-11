/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/storage',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer'
    ],
    function (Component, storage, ko, fullScreenLoader, quote,  urlBuilder,  baseUrlBuilder, customer) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payever_Payever/payment/form',
                transactionResult: '',
                isCreatePaymentCalled: false,
                getIframeUrl:'',
                getPayeverError:false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isCreatePaymentCalled',
                        'getIframeUrl',
                        'getHeightIframe',
                        'getPayeverError'
                    ]);
                this.isCreatePaymentCalled(false);
                this.getPayeverError(false);

                return this;
            },

            getCode: function () {

                return this.item.method;
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': null
                };
            },

            selectPayeverPaymentMethod: function () {
                this.isCreatePaymentCalled(false);
                this.getPayeverError(false);
                this.getIframeUrl('');
                return this.selectPaymentMethod();
            },


            createPayment: function () {
                var params, createPaymentUrl;
                this.getPayeverError(false);
                params = {
                    cartId: quote.getQuoteId(),
                    billingAddress: quote.billingAddress(),
                    paymentMethod: this.getData()
                };

                if (customer.isLoggedIn()) {
                    createPaymentUrl = urlBuilder.createUrl('/create-payment', {});
                } else {
                    createPaymentUrl = urlBuilder.createUrl('/guest-create-payment/:quoteId', {
                        quoteId: quote.getQuoteId()
                    });
                    params.email = quote.guestEmail;
                }

                var that = this;
                this.isCreatePaymentCalled(true);
                fullScreenLoader.startLoader();
                /** Your function for ajax call */
                storage.post(
                    createPaymentUrl,
                    JSON.stringify(params)
                ).done(
                    function (response) {
                        fullScreenLoader.stopLoader();

                        if (response.error) {
                            that.getPayeverError(response.error);
                            that.isCreatePaymentCalled(false);
                        } else {
                            if (that.getModeIntegration() == 'redirect_iframe') {
                                window.location.href = that.getRedirectIframeUrl(response.url);
                            } else if (that.getModeIntegration() == 'iframe') {
                                that.prepareIframeBlock(response, that);
                            } else if (that.getModeIntegration() == 'redirect') {
                                window.location.href = response.url;
                            }
                        }
                    }
                ).fail(
                    function (response) {
                        fullScreenLoader.stopLoader();
                        if (response.error) {
                            that.getPayeverError(response.error);
                        }
                        that.isCreatePaymentCalled(false);
                    }
                );
                return true;
            },

            placeOrder: function () {
                this.createPayment();
            },

            prepareIframeBlock: function (response, that) {
            
                that.getIframeUrl(response.url);

                if (window.addEventListener) {
                    window.addEventListener("message", onMessagePayever, false);
                } else if (window.attachEvent) {
                    window.attachEvent("onmessage", onMessagePayever, false);
                }

                function onMessagePayever(event)
                {
                    if (event.data && event.data.event == "payeverCheckoutHeightChanged") {
                        var value = Math.max(0, parseInt(event.data.value));
                        if (value > 0) {
                            that.getHeightIframe(value + 'px');
                        }
                    }
                }
            },
            /**
             * Get icon URL
             *
             * @returns {String}
             */
            getIconUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].iconUrl;
            },

            getModeIntegration: function () {
                return window.checkoutConfig.payment[this.getCode()].modeIntegration;
            },

            getRedirectIframeUrl: function (url) {
                var params = {
                    'url': url,
                    'method':this.getTitle()
                };

                return baseUrlBuilder.build('payever/payment/iframe?' + btoa(JSON.stringify(params)));
            },
            /**
             * Get payment title
             *
             * @returns {String}
             */
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },

            /**
             * Get payment description
             *
             * @returns {String}
             */
            getDescription: function () {
                return window.checkoutConfig.payment[this.getCode()].description;
            }

        });
    }
);
