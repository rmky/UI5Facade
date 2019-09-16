/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

sap.ui.define([
	"sap/base/util/merge",
	"sap/ui/fl/apply/_internal/connectors/BrowserStorageConnector"
], function(
	merge,
	BrowserStorageConnector
) {
	"use strict";

	var oMyStorage = {
		_items: {},
		setItem: function(sKey, vValue) {
			this._items[sKey] = vValue;
		},
		clear: function() {
			this._items = {};
		},
		getItem: function(sKey) {
			return this._items[sKey];
		}
	};

	/**
	 * Connector that retrieves data from an internal object.
	 *
	 * @namespace sap.ui.fl.apply._internal.connectors.JsObjectConnector
	 * @experimental Since 1.70
	 * @since 1.70
	 * @private
	 * @ui5-restricted sap.ui.fl.apply._internal.Connector
	 */
	var JsObjectConnector = merge({}, BrowserStorageConnector, /** @lends sap.ui.fl.apply._internal.connectors.JsObjectConnector */ {
		oStorage: oMyStorage
	});

	return JsObjectConnector;
}, true);
