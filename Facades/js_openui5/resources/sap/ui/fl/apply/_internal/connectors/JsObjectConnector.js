/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/apply/_internal/connectors/BrowserStorageConnector"],function(m,B){"use strict";var M={_items:{},setItem:function(k,v){this._items[k]=v;},clear:function(){this._items={};},getItem:function(k){return this._items[k];}};var J=m({},B,{oStorage:M});return J;},true);
