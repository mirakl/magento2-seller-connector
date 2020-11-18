define([
    'uiRegistry',
    'underscore',
    'Magento_Rule/rules'
], function (uiRegistry, _, VarienRulesForm) {
    'use strict';

    VarienRulesForm.prototype = _.extend(VarienRulesForm.prototype, {
        oldHideParamInputField: VarienRulesForm.prototype.hideParamInputField,

        hideParamInputField: function (container, event) {
            uiRegistry.get("mirakl_seller_listing_form.areas.mirakl_seller_listing_form_filter_products.mirakl_seller_listing_form_filter_products", function (element) {
                element.bubble('update', true);
            });

            return this.oldHideParamInputField(container, event);
        },

        oldRemoveRuleEntry: VarienRulesForm.prototype.removeRuleEntry,

        removeRuleEntry: function (container, event) {
            uiRegistry.get("mirakl_seller_listing_form.areas.mirakl_seller_listing_form_filter_products.mirakl_seller_listing_form_filter_products", function (element) {
                element.bubble('update', true);
            });

            return this.oldRemoveRuleEntry(container, event);
        }
    });

    return VarienRulesForm;
});
