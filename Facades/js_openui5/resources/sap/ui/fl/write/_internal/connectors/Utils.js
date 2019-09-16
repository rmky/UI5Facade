/*
 * ! OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/apply/_internal/connectors/Utils"],function(A){"use strict";var W="sap/ui/fl/write/_internal/connectors/";var u=function(p){return A.sendRequest(p.tokenUrl,"HEAD").then(function(r){if(r&&r.token){p.applyConnector.sXsrfToken=r.token;p.token=r.token;return p;}},function(e){return Promise.reject(e);});};return{getWriteConnectors:function(){return A.getConnectors(W,false);},getRequestOptions:function(a,t,p,c,d){var o={token:a.sXsrfToken,tokenUrl:t,applyConnector:a};if(p){o.payload=JSON.stringify(p);}if(c){o.contentType=c;}if(d){o.dataType=d;}return o;},sendRequest:function(U,m,p){return A.sendRequest(U,m,p).then(function(r){return r.response;},function(f){if(f.status===403){return u(p).then(A.sendRequest.bind(undefined,U,m)).then(function(r){return r.response;});}throw f;}).catch(function(e){return Promise.reject(e);});}};});
