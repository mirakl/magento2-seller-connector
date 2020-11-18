define([
    'jquery'
], function ($) {
    'use strict';

    return function (validator) {

        validator.addRule(
            'validate-api-url',
            function (value, params, additionalParams) {
                var expression = /http(s):\/\/.(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)api/gi;
                var regex = new RegExp(expression);

                return value.length == 0 || value.match(regex);
            },
            $.mage.__('Please enter a valid Mirakl URL. Protocol https:// is required (https://your_mirakl_env/api).')
        );

        return validator;
    };
});