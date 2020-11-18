define([
    'underscore',
    'mage/translate',
    'uiRegistry',
    'mageUtils',
    'uiLayout',
    'Magento_Ui/js/form/components/collection'
], function (_, $t, registry, utils, layout,UiCollection) {
    'use strict';

    return UiCollection.extend({
        defaults: {
            lastIndex: 0,
            template: 'mirakl_seller_core/form/components/additional-fields',
            defaultChildTemplate: null
        },

        initialize: function () {
            this._super();

            // Add words in js translation dictionary
            $t('Default:');
            $t('Attribute:');

            return this;
        },

        addChild: function (index) {
            this.childIndex = !_.isString(index) ?
                'new_' + this.lastIndex++ :
                index;

            var childTemplate = {
                parent: '${ $.$data.name }',
                name: '${ $.$data.childIndex }',
                dataScope: '${ $.name }',
                nodeTemplate: '${ $.$data.name }.${ $.$data.childIndex }'
            };

            layout([utils.template(childTemplate, this)]);

            return this;
        }
    });
});
