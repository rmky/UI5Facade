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
			var ICUFormat = undefined;
			if (this.options.dateFormat) {
				ICUFormat = this.options.dateFormat;
			}
			return exfTools.time.parse(sTime, ICUFormat);
		},			
		
		formatValue: function (sTime) {
			var ICUFormat = undefined;
			if (this.options.dateFormat) {
				ICUFormat = this.options.dateFormat;
			}
			return exfTools.time.format(sTime, ICUFormat);			
		},
		
		validateValue: function (sInternalValue) {			
			return exfTools.time.validate(sInternalValue);
		},
	});
});