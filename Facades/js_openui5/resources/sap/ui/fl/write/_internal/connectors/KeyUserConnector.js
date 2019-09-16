/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/write/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/KeyUserConnector","sap/ui/fl/apply/_internal/connectors/Utils","sap/ui/fl/write/_internal/connectors/Utils"],function(m,B,A,a,W){"use strict";var b="/v1";var R={CHANGES:"/changes/",SETTINGS:"/settings"};var K=m({},B,{reset:function(p){var P=["reference","appVersion","generator"];var c=a.getSubsetOfObject(p,P);if(p.selectorIds){c.selector=p.selectorIds;}if(p.changeTypes){c.changeType=p.changeTypes;}delete p.reference;var r=a.getUrl(b+R.CHANGES,p,c);var o=W.getRequestOptions(A,b+R.SETTINGS);return W.sendRequest(r,"DELETE",o);},writeFlexData:function(p){var w=a.getUrl(b+R.CHANGES,p);var r=W.getRequestOptions(A,b+R.SETTINGS,p.payload,"application/json; charset=utf-8","json");return W.sendRequest(w,"POST",r);}});return K;},true);
