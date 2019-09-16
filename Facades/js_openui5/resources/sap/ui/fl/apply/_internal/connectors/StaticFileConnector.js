/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/apply/connectors/BaseConnector","sap/base/Log","sap/base/util/LoaderExtensions"],function(m,B,L,a){"use strict";var S=m({},B,{CONFIGURATION:{layerFilter:[],connectorName:"StaticFileConnector"},loadFlexData:function(p){var r=p.reference;var R=r.replace(/\./g,"/")+"/changes/changes-bundle.json";var c=!!sap.ui.loader._.getModuleState(R);var C=sap.ui.getCore().getConfiguration();if(c||C.getDebug()||C.isFlexBundleRequestForced()){try{var o={changes:a.loadResource(R)};return Promise.resolve(o);}catch(e){L.warning("flexibility did not find a changes-bundle.json for the application: "+r);}}return Promise.resolve({});}});return S;},true);
