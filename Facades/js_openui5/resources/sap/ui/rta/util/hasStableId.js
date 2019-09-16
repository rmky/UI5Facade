/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/Utils","sap/ui/dt/OverlayUtil"],function(F,O){"use strict";function c(o,s){var a=O.getAggregationInformation(o,o.getElement().sParentAggregationName);if(!a.templateId){return true;}return!F.checkControlId(a.templateId,s.appComponent);}return function hasStableId(e){if(!e||e._bIsBeingDestroyed){return false;}if(typeof e.data("hasStableId")!=="boolean"){var s=e.getDesignTimeMetadata().getStableElements(e);var u=(s.length>0?(s.some(function(S){var C=S.id||S;if(!F.checkControlId(C,S.appComponent)){return c(e,S);}})):true);e.data("hasStableId",!u);}return e.data("hasStableId");};});
