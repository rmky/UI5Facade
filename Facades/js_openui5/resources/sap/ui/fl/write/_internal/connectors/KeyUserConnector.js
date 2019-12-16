/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/write/_internal/connectors/BackendConnector","sap/ui/fl/apply/_internal/connectors/KeyUserConnector","sap/ui/fl/Layer"],function(m,B,A,L){"use strict";var P="/flex/keyuser";var a="/v1";var K=m({},B,{layers:[L.CUSTOMER],ROUTES:{CHANGES:P+a+"/changes/",SETTINGS:P+a+"/settings",TOKEN:P+a+"/settings"}});K.applyConnector=A;return K;},true);
