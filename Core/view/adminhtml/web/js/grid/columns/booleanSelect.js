/**
 * @api
 */
define([
    'Magento_Ui/js/grid/columns/select'
], function (Select) {
    'use strict';

    return Select.extend({
        /**
         * Retrieves label associated with a provided value.
         *
         * @returns {String}
         */
        getLabel: function (record) {
            var value = record[this.index],
                label = this._super(),
                cssClass = value == 1 ? 'grid-severity-notice' : 'grid-severity-critical';

            return '<span class="' + cssClass + '"><span>' + label + '</span></span>';
        }
    });
});