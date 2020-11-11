(function () {
	"use strict";
	
	/**
	 * A custom version of sap.m.StepInput, that can be empty.
	 * 
	 * Technically, it introduces an internal state flage `this._bTempValueIsEmpty`, that
	 * is true if it's an empty value - even if `this._fTempValue` is `0` at that time.
	 * Basically the flag tells us, if empty was "meant" or not.
	 */
	sap.m.StepInput.extend("exface.ui5Custom.StepInputCustom", { 
		renderer: {},
	
		/**
		 * Returns a default value depending of the given value, min and max properties.
		 *
		 * @param {number} value Indicates the value
		 * @param {number} max Indicates the max
		 * @param {number} min Indicates the min
		 * @returns {number} The default value
		 * @private
		 */
		_getDefaultValue : function (value, max, min) {
			if (value === "" || value === undefined || value === null) {
				this._bTempValueIsEmpty = true;
				return "";
			} else {
				this._bTempValueIsEmpty = false;
			}
			return sap.m.StepInput.prototype._getDefaultValue.call(this, value, max, min);
		},
		
		/**
		 * Formats the <code>vValue</code> accordingly to the <code>displayValuePrecision</code> property.
		 * if vValue is undefined or null, the property <code>value</code> will be used.
		 *
		 * @returns formated value as a String
		 * @private
		 */
		_getFormattedValue : function (vValue) {
			if (vValue === "" || vValue === undefined || vValue === null) {
				return "";
			}
			return sap.m.StepInput.prototype._getFormattedValue.call(this, vValue);
		},
		
		/*
		 * Sets the <code>value</code> by doing some rendering optimizations in case the first rendering was completed.
		 * Otherwise the value is set in onBeforeRendering, where we have all needed parameters for obtaining correct value.
		 * @param {object} oValue The value to be set
		 *
		 */
		setValue : function (oValue) {
			if (oValue === "" || oValue === undefined || oValue === null) {
				this._bTempValueIsEmpty = true;
				this._getInput().setValue("");
				this._disableButtons(0, this.getMax(), this.getMin());
				return this.setProperty("value", "", true);
			} else {
				this._bTempValueIsEmpty = false;
			}
			return sap.m.StepInput.prototype.setValue.call(this, oValue);
		},
		
		validateProperty : function(sPropertyName, oValue) {
			if (sPropertyName === "value" && (oValue === null || oValue === "" || oValue === undefined)) {
				return "";
			}
			return sap.m.StepInput.prototype.validateProperty.call(this, sPropertyName, oValue);
		},
		
		/**
		 * Changes the value of the control and fires the change event.
		 *
		 * @param {boolean} bForce If true, will force value change
		 * @returns {sap.m.StepInput} Reference to the control instance for chaining
		 * @private
		 */
		_changeValue : function (bForce) {
			// FIXME originally the change event is only fired when the value changes,
			// but currently there seems to be no way to detect a change because _fOldValue
			// and _fTempValue both could be zero in case empty and in case really ZERO.
			if (this._bTempValueIsEmpty) {
				// change the value and fire the event
				this.setValue("");
				this.fireChange({value: ""});
				return this;
			}
			return sap.m.StepInput.prototype._changeValue.call(this, bForce);
		},
		
		/**
		 * Applies change on the visible value but doesn't force the other checks that come with <code>this.setValue</code>.
		 * Usable for Keyboard Handling when resetting initial value with ESC key.
		 *
		 * @param {float} fNewValue The new value to be applied
		 * @private
		 */
		_applyValue : function (fNewValue) {
			if (this._bTempValueIsEmpty && ! fNewValue) {
				// change the value and fire the event
				this._getInput.setValue("");
				return this;
			} else {
				this._bTempValueIsEmpty = false;
			}
			return sap.m.StepInput.prototype._applyValue.call(this, fNewValue);
		},
		
		/**
		 * Handles the press of the increase/decrease buttons.
		 *
		 * @param {float} fMultiplier Indicates the direction - increment (positive value)
		 * or decrement (negative value) and multiplier for modifying the value
		 * @returns {sap.m.StepInput} Reference to the control instance for chaining
		 * @private
		 */
		_handleButtonPress : function (fMultiplier)	{
			// If it's an empty value, set it to 0 to restore original functionality completely.
			if (this._getInput().getValue() === '') {
				this._getInput().setValue(this._getFormattedValue(this._getDefaultValue(0)));
			}
			this._bTempValueIsEmpty = false;
			return sap.m.StepInput.prototype._handleButtonPress.call(this, fMultiplier);
		}
	});
})();