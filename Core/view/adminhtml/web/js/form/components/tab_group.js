define([
    'underscore'
], function (_) {
    'use strict';

    return function (TabGroup) {
        return TabGroup.extend({
            initialize: function () {
                this._super();
                this.template = 'mirakl_seller_core/form/components/tab';
            },

            /**
             * Activate an element according to the anchor (or the first in the list
             * if there are no anchors) or if it has the 'active' property set to true.
             *
             * @param  {Object} elem
             * @returns {Object} - reference to instance
             */
            initActivation: function (elem) {
                var elems   = this.elems(),
                    activeElem = 0,
                    hash = window.location.hash.substr(1);

                if (hash.length) {
                    _.each(elems, function (curElem) {
                        if (curElem.index == hash) {
                            activeElem = elems.indexOf(curElem);
                        }
                    });
                }

                if (elems.indexOf(elem) == activeElem || elem.active()) {
                    elem.activate();
                }

                return this;
            },

            /**
             * Delegates 'validate' method on element, then reads 'invalid' property
             * of params storage, and if defined, activates element, sets
             * 'allValid' property of instance to false and sets invalid's
             * 'focused' property to true.
             *
             * @param {Object} elem
             */
            validate: function (elem) {
                // Pass through if element is not listing form
                if (elem.ns !== 'mirakl_seller_listing_form') {
                    return this._super();
                }

                var result = elem.delegate('validate'),
                    invalid;

                invalid = _.find(result, function (item) {
                    if (item === undefined) {
                        return 0;
                    }

                    return !item.valid;
                });

                if (invalid) {
                    elem.activate();
                    invalid.target.focused(true);
                }

                return invalid;
            }
        });
    }
});