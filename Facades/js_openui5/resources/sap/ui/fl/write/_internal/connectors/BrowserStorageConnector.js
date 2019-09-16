/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/base/util/merge","sap/ui/fl/write/connectors/BaseConnector","sap/ui/fl/apply/_internal/connectors/BrowserStorageUtils"],function(m,B,a){"use strict";var b=m({},B,{oStorage:undefined,saveChange:function(i,c){var C;var s;if(i&&c){if(c.fileType==="ctrl_variant"&&c.variantManagementReference){C=a.createVariantKey(i);}else{C=a.createChangeKey(i);}s=JSON.stringify(c);this.oStorage.setItem(C,s);return C;}}});return b;},true);
