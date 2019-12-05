(function () {
	"use strict";
	
	/**
	 * A custom version of sap.m.StepInput, that can be empty.
	 */
	sap.m.StepInput.extend("exface.ui5Custom.StepInputCustom", { 
		renderer: {},
	
		_getDefaultValue : function (value, max, min) {
			if (value === "" || value === undefined || value === null) {
				return "";
			}
			return sap.m.StepInput.prototype._getDefaultValue.call(this, value, max, min);
		},
		
		_getFormatedValue : function (vValue) {
			if (vValue === "" || vValue === undefined || vValue === null) {
				return "";
			}
			return sap.m.StepInput.prototype._getFormatedValue.call(this, vValue);
		},
		
		setValue : function (oValue) {
			if (oValue === "" || oValue === undefined || oValue === null) {
				this._getInput().setValue("");
				this._disableButtons(0, this.getMax(), this.getMin());
				return this.setProperty("value", "", true);
			}
			return sap.m.StepInput.prototype.setValue.call(this, oValue);
		},
		
		validateProperty : function(sPropertyName, oValue) {
			if (sPropertyName === "value" && (oValue === null || oValue === "" || oValue === undefined)) {
				return "";
			}
			return sap.m.StepInput.prototype.validateProperty.call(this, sPropertyName, oValue);
		}
	});
})();