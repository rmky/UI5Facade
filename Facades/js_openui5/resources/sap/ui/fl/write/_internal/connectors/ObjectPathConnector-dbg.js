/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */

sap.ui.define([
	"sap/base/util/merge",
	"sap/ui/fl/write/connectors/BaseConnector",
	"sap/ui/fl/apply/_internal/connectors/ObjectPathConnector"
], function(
	merge,
	BaseConnector,
	ApplyObjectPathConnector
) {
	"use strict";

	/**
	 * Empty connector since we don't support writing to a file.
	 *
	 * @namespace sap.ui.fl.write._internal.connectors.ObjectPathConnector
	 * @since 1.73
	 * @version 1.73.1
	 * @private
	 * @ui5-restricted sap.ui.fl.write._internal.Storage
	 */
	return merge({}, BaseConnector, /** @lends sap.ui.fl.write._internal.connectors.ObjectPathConnector */ {
		layers: [],

		loadFeatures: function (mPropertyBag) {
			return new Promise(function(resolve, reject) {
				var sPath = ApplyObjectPathConnector.jsonPath || mPropertyBag.path;
				if (sPath) {
					jQuery.getJSON(sPath).done(function (oResponse) {
						oResponse.componentClassName = mPropertyBag.flexReference;
						resolve(oResponse.settings);
					}).fail(reject);
				} else {
					resolve({});
				}
			});
		}
	});
}, true);
