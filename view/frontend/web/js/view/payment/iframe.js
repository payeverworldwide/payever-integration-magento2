define([
    'jquery',
    'uiComponent',
    'underscore'
], function ($, Component, _) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Payever_Payever/payment/iframe'
        },

        parseUrl: function () {
            var urlSearch = location.search;
            return JSON.parse(atob(urlSearch.substring(1)));

        },

        initObservable: function () {
            this._super().observe([
                'getTitle',
                'getIframeUrl',
                'getHeight'
            ]);
            this.getHeight('500px');

            return this;
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            var data = this.parseUrl();
            this.getIframeUrl(data['url']);
            this.getTitle(data['method']);

            var height = this.getHeight;

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
                        height(value + 'px');
                    }
                }
            }
        }
    });
});
