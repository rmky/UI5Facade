/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define([],function(){"use strict";var D={isKeyUser:false,isVariantSharingEnabled:false,isAtoAvailable:false,isAtoEnabled:false,isProductiveSystem:true,isZeroDowntimeUpgradeRunning:false,system:"",client:""};return{mergeResults:function(r){var R=D;r.forEach(function(o){if(o.response){Object.keys(o.response).forEach(function(k){R[k]=o.response[k];});}});return R;}};});
