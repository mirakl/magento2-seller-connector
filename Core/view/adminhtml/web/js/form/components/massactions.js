define([
    'underscore',
    'uiRegistry',
    'mageUtils',
    'Magento_Ui/js/grid/massactions'
], function (_, registry, utils, MassActions) {
    'use strict';

    return MassActions.extend({
        /**
         * Creates action callback based on its' data. If action doesn't specify
         * a callback function than the default one will be used.
         *
         * @private
         * @param {Object} action - Actions' object.
         * @param {Object} selections - Selections data.
         * @returns {Function} Callback function.
         */
        _getCallback: function (action, selections) {
            var listing = registry.get(this.source.ns + '.' + this.source.parentName + '.product_columns');

            return function() {
                listing.executeMassAction(action, selections);
            }
        }
    });
});
