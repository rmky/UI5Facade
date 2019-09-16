/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/apply/_internal/connectors/Utils"],function(A){"use strict";var R={FLEX_DATA:{changes:[],variantSection:{}}};function l(p,c){var a=c.map(function(o){var b=Object.assign(p,{url:o.url});return o.connector.loadFlexData(b).catch(A.logAndResolveDefault.bind(undefined,R.FLEX_DATA,o,"loadFlexData"));});return Promise.all(a);}var C={};C.loadFlexData=function(p){if(!p||!p.reference){return Promise.reject("loadFlexData: No reference was provided.");}return A.getApplyConnectors().then(l.bind(this,p)).then(A.mergeResults);};return C;},true);
