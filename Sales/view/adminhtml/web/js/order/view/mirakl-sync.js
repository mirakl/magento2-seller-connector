define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'domReady!'
], function ($, confirmation) {
    'use strict';
    return function (config) {
        $(document).ready(function () {
            var title = $.mage.__('Unsync Mirakl order');
            var content = $.mage.__(
                'Are you sure you want to unsync the order with Mirakl?' +
                '<br>Any modifications made in your Magento will not be pushed to Mirakl.' +
                '<br>You won’t be able to re-sync this order with Mirakl afterwards.'
            );
            $('#mirakl-sync-link').on('click', function (e) {
                e.preventDefault();
                confirmation({
                    title: title,
                    content: content,
                    actions: {
                        confirm: function () {
                            window.location.href = config.unsyncUrl;
                        },
                        cancel: function () {
                            return false;
                        }
                    },
                    buttons: [{
                        text: $.mage.__('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }, {
                        text: $.mage.__('Confirm'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }]
                });
            });
        });
    }
})