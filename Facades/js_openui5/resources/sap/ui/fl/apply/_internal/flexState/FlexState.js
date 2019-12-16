/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/apply/_internal/flexState/prepareMap","sap/base/util/merge"],function(p,m){"use strict";var _={};return{initMaps:function(P){if(!_[P.reference]){_[P.reference]={};}if(!_[P.reference].state){m(_[P.reference],p(P),{state:P.flexResponse});}},getState:function(r){if(_[r]){return _[r].state;}},clearStates:function(){_={};},clearState:function(r){if(_[r]){_[r]={};}},getVariantsMap:function(r){if(_[r]){return _[r].variantsMap;}},getChangesMap:function(r){if(_[r]){return _[r].changesMap;}},getAppDescriptorMap:function(r){if(_[r]){return _[r].appDescriptorMap;}}};},true);
