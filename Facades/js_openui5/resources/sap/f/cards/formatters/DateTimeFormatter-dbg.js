/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([
	"sap/ui/core/format/DateFormat",
	"sap/ui/core/date/UniversalDate"
], function (
	DateFormat,
	UniversalDate
) {
	"use strict";

	/**
	 * Contains functions that can format date and time
	 *
	 * @namespace
	 * @private
	 */
	var oDateTimeFormatters = {

		/**
		 * Formats date and time.
		 * @param {string|number|object} vDate Any string and number from which Date object can be created, or a Date object.
		 * @param {object} oFormatOptions All format options which sap.ui.core.format.DateFormat.getDateTimeInstance accepts.
		 * @returns {string} The formatted date.
		 */
		date: function (vDate, oFormatOptions) {

			var oDateFormat = DateFormat.getDateTimeInstance(oFormatOptions);

			// Calendar is determined base on sap.ui.getCore().getConfiguration().getCalendarType()
			var oUniversalDate = new UniversalDate(vDate);
			var sFormattedDate = oDateFormat.format(oUniversalDate);

			return sFormattedDate;
		}
	};

	return oDateTimeFormatters;
});