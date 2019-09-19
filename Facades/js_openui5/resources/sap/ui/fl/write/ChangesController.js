/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/FlexControllerFactory","sap/ui/fl/Utils"],function(O,F){"use strict";var C={getFlexControllerInstance:function(m){return typeof m==="string"?O.create(m):O.createForControl(m);},getDescriptorFlexControllerInstance:function(m){var a=F.getAppDescriptorComponentObjectForControl(m);return O.create(a.name,a.version);}};return C;},true);
