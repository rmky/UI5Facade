/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/write/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/Utils"],function(m,B,A){"use strict";var R={CHANGES:"/changes/",VARIANTS:"/variants/"};var F={isProductiveSystem:true};var P=m({},B,{writeFlexData:function(p){var w=A.getUrl(R.CHANGES,p);return A.sendRequest(w,"POST",p.payload);},reset:function(p){var a=["reference","appVersion","generator"];var b=A.getSubsetOfObject(p,a);if(p.selectorIds){b.selector=p.selectorIds;}if(p.changeTypes){b.changeType=p.changeTypes;}delete p.reference;var r=A.getUrl(R.CHANGES,p,b);return A.sendRequest(r,"DELETE");},loadFeatures:function(){return Promise.resolve(F);}});return P;},true);
