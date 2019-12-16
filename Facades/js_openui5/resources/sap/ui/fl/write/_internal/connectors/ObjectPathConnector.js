/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/write/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/ObjectPathConnector"],function(m,B,A){"use strict";return m({},B,{layers:[],loadFeatures:function(p){return new Promise(function(r,a){var P=A.jsonPath||p.path;if(P){jQuery.getJSON(P).done(function(R){R.componentClassName=p.flexReference;r(R.settings);}).fail(a);}else{r({});}});}});},true);
