/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var F="sap.ui.fl.change";var a="sap.ui.fl.variant";return{forEachChangeInStorage:function(s,p){var k=Object.keys(s);k.forEach(function(K){if(K.includes(F)||K.includes(a)){p(K);}});},createChangeKey:function(i){if(i){return F+"."+i;}},createVariantKey:function(i){if(i){return a+"."+i;}},sortGroupedFlexObjects:function(r){function b(c,C){return new Date(c.creation)-new Date(C.creation);}["changes","variantChanges","variants","variantDependentControlChanges","variantManagementChanges"].forEach(function(s){r[s]=r[s].sort(b);});return r;}};});
