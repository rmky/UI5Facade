sap.ui.define([
	"sap/ui/model/SimpleType",
	"./moment.min",
	"./exfTools"
], function (SimpleType) {
	"use strict";


    return SimpleType.extend("TimeType", {
		
		
		constructor: function(data) {
			if (data) {
				this.options = data;
			} else {
				this.options = {};
			}
		},
		
		parseValue: function (time) {
			var ParseParams = undefined;
			if (this.options.ParseParams) {
				ParseParams = this.options.ParseParams;
			}
			return exfTools.time.parse(time);
		},			
		
		formatValue: function (time) {
			var phpFormat = undefined;
			if (this.options.dateFormat) {
				phpFormat = this.options.dateFormat;
			}
			return exfTools.time.format(time, phpFormat);			
		},
		
		validateValue: function (InternalValue) {			
			return exfTools.time.validate(InternalValue);
		},
	});
});