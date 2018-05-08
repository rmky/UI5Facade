sap.ui.define([
	"sap/ui/core/UIComponent"
], function (UIComponent) {
	"use strict";

	return UIComponent.extend("[#app_id#].Component", {

		metadata: {
			manifest: "json"
		},

        init: function () {
            // call the init function of the parent
            UIComponent.prototype.init.apply(this, arguments);

            // create the views based on the url/hash
            this.getRouter().initialize();
        },
		
		findViewOfControl: function(oControl) {
			var sName;
			do {
				oControl = oControl.getParent();
				sName = oControl.getMetadata().getName()
			} while (sName !== 'sap.ui.core.mvc.JSView' && sName !== 'sap.ui.core.mvc.XMLView');
			return oControl;
		}

	});

});
