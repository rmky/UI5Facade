/**
 * Define a custom control to display filters in the P13nDialog as the default filters are always
 * plain text inputs - no combos etc. possible.
 */
(function () {
	"use strict";

	/** @lends sap.m.sample.P13nDialogWithCustomPanel.CustomPanel */
	sap.m.P13nPanel.extend("exface.openui5.P13nLayoutPanel", {
		constructor: function (sId, mSettings) {
			sap.m.P13nPanel.apply(this, arguments);
		},
		metadata: {
			library: "sap.m",
			aggregations: {
				content: {
					type: "sap.ui.core.Control",
					multiple: false,
					singularName: "content"
				}
			}
		},
		renderer: function (oRm, oControl) {
			if (!oControl.getVisible()) {
				return;
			}
			oRm.renderControl(oControl.getContent());
		}
	});
})();