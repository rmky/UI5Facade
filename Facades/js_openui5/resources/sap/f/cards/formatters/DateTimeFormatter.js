/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/core/format/DateFormat","sap/ui/core/date/UniversalDate"],function(D,U){"use strict";var d={date:function(v,f){var o=D.getDateTimeInstance(f);var u=new U(v);var F=o.format(u);return F;}};return d;});
