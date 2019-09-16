/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/apply/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/Utils"],function(m,B,A){"use strict";var R={FLEX_DATA:"/flex/data/"};var P=m({},B,{loadFlexData:function(p){var a=A.getSubsetOfObject(p,["appVersion"]);var d=A.getUrl(R.FLEX_DATA,p,a);return A.sendRequest(d,"GET",{token:this.sXsrfToken}).then(function(r){var o=r.response;if(r.token){this.sXsrfToken=r.token;}return o;}.bind(this));}});return P;},true);
