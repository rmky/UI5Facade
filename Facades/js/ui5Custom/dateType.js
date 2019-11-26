sap.ui.define([
	"sap/ui/model/SimpleType",
], function (SimpleType) {
	"use strict";


    return SimpleType.extend("DateType", {
		
		
		constructor: function(data) {
			if (data) {
				this.options = data;
			} else {
				this.options = {};
			}
		},
		
		parseValue: function (date) {
			var ParseParams = undefined;
			if (this.options.ParseParams) {
				ParseParams = this.options.ParseParams;
			}
			return exfTools.date.parse(date, ParseParams);
		},			
		
		formatValue: function (sDate) {
			var phpFormat = undefined;
			if (this.options.dateFormat) {
				phpFormat = this.options.dateFormat;
			}
			return exfTools.date.format(sDate, phpFormat);			
		},
		
		validateValue: function (sInternalValue) {			
			return exfTools.date.validate(sInternalValue);
		},
	});
});