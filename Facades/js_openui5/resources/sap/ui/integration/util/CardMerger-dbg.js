/*!
 * OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge"], function(merge) {
	"use strict";

	var CardMerger = {
		mergeCardDelta: function(oManifest, aChanges) {
			var oInitialManifest = merge({}, oManifest),
				sSection = "sap.card";

			aChanges.forEach(function(oChange) {
				merge(oInitialManifest[sSection], oChange.content);
			});
			return oInitialManifest;
		},

		mergeCardDesigntimeMetadata: function(oDesigntimeMetadata, aChanges) {
			var oInitialDTMedatada = merge({}, oDesigntimeMetadata);

			aChanges.forEach(function(oChange) {
				var aInlineChanges = oChange.content.entityPropertyChange || [];

				aInlineChanges.forEach(function(oInlineChange) {
					var sPropertyPath = oInlineChange.propertyPath;
					switch (oInlineChange.operation) {
						case "UPDATE":
							if (oInitialDTMedatada.hasOwnProperty(sPropertyPath)) {
								oInitialDTMedatada[sPropertyPath] = oInlineChange.propertyValue;
							}
							break;
						case "DELETE":
							delete oInitialDTMedatada[sPropertyPath];
							break;
						case "INSERT":
							if (!oInitialDTMedatada.hasOwnProperty(sPropertyPath)) {
								oInitialDTMedatada[sPropertyPath] = oInlineChange.propertyValue;
							}
							break;
						default:
							break;
					}
				});
			});

			return oInitialDTMedatada;
		}
	};

	return CardMerger;
});