define([
    'Magento_Ui/js/grid/listing',
    'jquery',
    'mage/translate',
    'uiRegistry',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm'
], function (Listing, $, $t, uiRegistry, alert, confirm) {
    'use strict';

    return Listing.extend({
        defaults: {
            modules: {
                source: '${ $.provider }'
            }
        },

        initObservable: function () {
            return this._super()
                .observe([
                    'loading'
                ]);
        },

        executeAction: function (index, functionName, row) {
            if (row.actions[index] && row.actions[index].confirm) {
                var callback = function() {
                    this[functionName](row);
                }.bind(this);

                confirm({
                    title: $t('Confirm'),
                    content: row.actions[index].confirm,
                    actions: {
                        confirm: callback
                    }
                });
            } else {
                this[functionName](row);
            }

            return false;
        },

        executeMassAction: function (action, selections) {
            this.loading(true);

            var ajaxSettings = {
                url: action.url,
                data: selections,
                method: 'POST',
                dataType: 'json'
            };

            var request = $.ajax(ajaxSettings);
            request
                .done(this.onRequestSuccess.bind(this))
                .fail(this.onError.bind(this));
        },

        onError: function (xhr) {
            this.loading(false);
            if (xhr.statusText === 'abort') {
                return;
            }

            alert({
                content: $t('Something went wrong.')
            });
        },

        onRequestSuccess: function (data) {
            var messages = data.messages || [];

            if (messages.length) {
                var block = $('[data-role=insert-listing-message]');
                if (block !== undefined && block.length) {
                    block.html('');
                } else {
                    block = $('<div data-role="insert-listing-message" class="messages"></div>');
                    $('[data-role=grid-wrapper]').prepend(block);
                }

                messages.forEach(function (message) {
                    block.append(
                        '<div class="message message-' + message.type + ' ' + message.type + '">' +
                        message.message + '</div>'
                    );
                });
            }

            if (data.refresh) {
                this.source().storage().clearRequests();
                this.source('reload');

                var productsGrid = 'mirakl_seller_listing_product_listing';
                productsGrid = productsGrid + '.' + productsGrid + '.' + productsGrid + '_columns';

                uiRegistry.get(productsGrid, function (element) {
                    element.source().storage().clearRequests();
                    element.source('reload');
                });
            }

            this.loading(false);
        },

        loading: function (isLoading) {
            var body = $('body').loader();
            body.loader(isLoading ? 'show' : 'hide');
        },

        updateTracking: function (row) {
            this.loading(true);

            var ajaxSettings = {
                url: row.actions.edit.href,
                method: 'GET',
                dataType: 'json'
            };

            var request = $.ajax(ajaxSettings);
            request
                .done(this.onRequestSuccess.bind(this))
                .fail(this.onError.bind(this));
        }
    });
});
