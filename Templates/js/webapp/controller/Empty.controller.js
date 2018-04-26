sap.ui.define([
	"[#component_path#]/controller/BaseController"
], function (BaseController) {
	"use strict";
	
	return BaseController.extend("[#app_id#].controller.[#controller_name#]", {

		[#controller_body#]

	});

});

$('head').append('[#html_head_tags#]');

