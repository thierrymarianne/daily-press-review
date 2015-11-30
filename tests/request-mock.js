'use strict';

/**
 * A factory producing request mocks
 *
 * @param {String} url
 * @param {Boolean} debug
 */
var RequestMockery = function (url, debug) {
    var shouldLogMocking = debug || false;

    return (function ($) {
        var mockFunctionNotCalled = 'The "mock" function has to be called ' +
            'before having access to the id of a request mock';
        var mockBeforeDestroy = 'The "destroy" function can not be called ' +
            'before the "mock" function has been called';

        var RequestMock = function (options, $) {
            this.options = options;
            this.$ = $;

            $.mockjaxSettings.throwUnmocked = true;
            $.mockjaxSettings.logging = shouldLogMocking;
            $.mockjaxSettings.responseTime = 0;
        };

        /**
         * Mock an asynchronous request
         *
         * @returns {RequestMock}
         */
        RequestMock.prototype.mock = function () {
            this.id = this.$.mockjax(this.options);

            return this;
        };

        /**
         * Ensure a request has been mocked already
         *
         * @returns {RequestMock}
         */
        RequestMock.prototype.ensureMockedRequest = function() {
            if (this.id === undefined) {
                throw mockFunctionNotCalled;
            }

            return this;
        };

        /**
         * Return the id of a request mock
         *
         * @returns {*}
         */
        RequestMock.prototype.getId = function () {
            return (this.ensureMockedRequest()).id;
        };

        /**
         * Returns the request handler
         *
         * @return {RequestMock}
         */
        RequestMock.prototype.getHandler = function () {
            return this.ensureMockedRequest()
            .$.mockjax.handler(this.id);
        };

        /**
         * Send data when requesting a URL
         * @param data
         */
        RequestMock.prototype.sendData = function (data) {
            this.options.data = data;
        };

        /**
         * Destroy a mocked request
         *
         * @returns {RequestMock}
         */
        RequestMock.prototype.destroy = function () {
            try {
                this.$.mockjax.clear(this.getId());
            } catch (error) {
                if (error === mockFunctionNotCalled) {
                    throw Error(mockBeforeDestroy);
                } else {
                    throw error;
                }
            }

            return this;
        };

        /**
         * Declare on after success callback
         *
         * @param url
         * @returns {RequestMock}
         */
        RequestMock.prototype.sendRequestToUrl = function (url) {
            this.options.url = url;

            return this;
        };

        /**
         * Set the response returned by the request
         *
         * @param response
         * @returns {RequestMock}
         */
        RequestMock.prototype.respondWith = function (response) {
            this.options.responseText = response;

            return this;
        };

        /**
         * Set the request handler
         *
         * @param {Function} handler
         * @return {RequestMock}
         */
        RequestMock.prototype.setRequestHandler = function (handler) {
            this.options.response = handler;

            return this;
        };

        /**
         * Set the request headers
         *
         * @param {Object} headers
         * @returns {RequestMock}
         */
        RequestMock.prototype.setRequestHeaders = function (headers) {
            if (
                headers.constructor &&
                headers.constructor.toString().indexOf('Object') === -1
            ) {
                throw 'Invalid headers (they should be passed as an object)';
            }
            this.options.headers = headers;

            return this;
        };

        /**
         * Set the response status code
         *
         * @param statusCode
         */
        RequestMock.prototype.setStatusCode = function (statusCode) {
            this.options.status = statusCode;
        };

        /**
         * Set on after success callback
         *
         * @param   {function} callback
         * @returns {RequestMock}
         */
        RequestMock.prototype.onAfterSuccess = function (callback) {
            this.options.onAfterSuccess = callback;

            return this;
        };

        /**
         * Set on after error callback
         *
         * @param   {function} callback
         * @returns {RequestMock}
         */
        RequestMock.prototype.onAfterError = function (callback) {
            if (typeof callback == 'function') {
                this.options.onAfterError = callback;
            }

            return this;
        };

        /**
         * Set on after complete callback
         *
         * @param callback
         * @returns {RequestMock}
         */
        RequestMock.prototype.onAfterComplete = function (callback) {
            this.options.onAfterComplete = callback;

            return this;
        };

        /**
         * Set the HTTP method to use for the request to GET
         *
         * @returns {RequestMock}
         */
        RequestMock.prototype.shouldGet = function () {
            this.options.type = 'GET';

            return this;
        };

        /**
         * Set the HTTP method to use for the request to POST
         *
         * @returns {RequestMock}
         */
        RequestMock.prototype.shouldPost = function () {
            this.options.type = 'POST';

            return this;
        };

        return (new RequestMock({url: url}, $)).shouldGet();
    })(window.jQuery);
};

window.RequestMockery = RequestMockery;