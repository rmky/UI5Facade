sap.ui.define([
	"sap/ui/model/SimpleType",
], function (SimpleType) {
	"use strict";


    return SimpleType.extend("exface.ui5Custom.dataTypes.MomentTimeType", {
		
		
		constructor: function(data) {
			if (data) {
				this.options = data;
			} else {
				this.options = {};
			}
		},
		
		parseValue: function (sTime) {
			return exfTools.time.parse(sTime);
		},			
		
		formatValue: function (sTime) {
			var phpFormat = undefined;
			if (this.options.dateFormat) {
				phpFormat = this.options.dateFormat;
			}
			return exfTools.time.format(sTime, phpFormat);			
		},
		
		validateValue: function (sInternalValue) {			
			return exfTools.time.validate(sInternalValue);
		},
	});
});