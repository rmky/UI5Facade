sap.ui.define([
	"[#component_path#]/controller/BaseController"
], function (BaseController) {
	"use strict";

	return BaseController.extend("[#app_id#].controller.[#controller_name#]", {

		onInit: function () {
			[#controller_methods#]
		}

	});

});

