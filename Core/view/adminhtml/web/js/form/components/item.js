define([
    'mage/translate',
    'Magento_Ui/js/form/components/collection/item'
], function ($t, UiItem) {
    'use strict';

    return UiItem.extend({
        defaults: {
            label: '',
            uniqueNs: 'activeCollectionItem',
            previewTpl: 'mirakl_seller_core/form/components/preview'
        },

        buildPreview: function (data) {
            var preview = this.getPreview(data.items),
                prefix = data.prefix;

            return '<strong>' + $t(prefix) + '</strong> ' + preview.join(data.separator);
        },

        additionalFieldName: function () {
            return $t('Name:') + ' ' + this.marketplaceValue.label;
        }
    });
});
