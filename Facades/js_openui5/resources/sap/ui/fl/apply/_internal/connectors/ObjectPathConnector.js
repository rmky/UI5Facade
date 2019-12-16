/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/apply/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/Utils"],function(m,B,U){"use strict";var O=m({},B,{setJsonPath:function(i){O.jsonPath=i;},loadFlexData:function(p){return new Promise(function(r,a){var P=O.jsonPath||p.path;if(P){jQuery.getJSON(P).done(function(R){R.componentClassName=p.flexReference;r(R);}).fail(a);}else{r(U.getEmptyFlexDataResponse());}});}});return O;},true);
