sap.ui.define([
	"[#component_path#]/controller/NotFound.controller"
], function (NotFoundController) {
	"use strict";

	return NotFoundController.extend("[#app_id#].controller.Offline", {
		onInit: function () {
			var oRouter, oTarget;
			oRouter = this.getRouter();
			oTarget = oRouter.getTarget("offline");
			oTarget.attachDisplay(function (oEvent) {
				this._oData = oEvent.getParameter("data"); //store the data
			}, this);
		}
	});

});

