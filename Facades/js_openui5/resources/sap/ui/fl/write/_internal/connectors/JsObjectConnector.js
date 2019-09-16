/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/apply/_internal/connectors/JsObjectConnector","sap/ui/fl/write/_internal/connectors/BrowserStorageConnector"],function(m,L,B){"use strict";var J=m({},B,{oStorage:{setItem:function(k,v){L.oStorage.setItem(k,v);},clear:function(){L.oStorage.clear();},getItem:function(k){return L.oStorage.getItem(k);},_items:L.oStorage._items}});return J;},true);
