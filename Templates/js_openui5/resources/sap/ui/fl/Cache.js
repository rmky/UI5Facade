/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/fl/LrepConnector","sap/ui/fl/Utils"],function(L,U){"use strict";var C=function(){};C._isOn=true;C._entries={};C._switches={};C._oFlexDataPromise=undefined;C.getSwitches=function(){return C._switches;};C.isActive=function(){return C._isOn;};C.setActive=function(a){C._isOn=a;};C.getFlexDataPromise=function(){return C._oFlexDataPromise;};C.getEntries=function(){return C._entries;};C.clearEntries=function(){C._entries={};};C.getEntry=function(c,a){if(!C._entries[c]){C._entries[c]={};}if(!C._entries[c][a]){C._entries[c][a]={file:{changes:{changes:[],contexts:[],variantSection:{},ui2personalization:{}}}};}return C._entries[c][a];};C.clearEntry=function(c,a){C.getEntry(c,a);C._entries[c][a]={};};C._deleteEntry=function(c,a){if(C._entries[c]&&C._entries[c][a]){delete C._entries[c][a];}if(jQuery.isEmptyObject(C._entries[c])){delete C._entries[c];}};C.getChangesFillingCache=function(l,c,p){if(!this.isActive()){return l.loadChanges(c,p);}var s=c.name;var a=c.appVersion||U.DEFAULT_APP_VERSION;var o=C.getEntry(s,a);if(o.promise){return o.promise;}var b=C._getChangesFromBundle(p);if(p&&p.cacheKey==="<NO CHANGES>"){var d=b.then(function(g){o.file={changes:{changes:g,contexts:[],variantSection:{},ui2personalization:{}},componentClassName:s};return o.file;});o.promise=d;return d;}var f=l.loadChanges(c,p);var e=f.then(function(r){return r;},function(E){var g=jQuery.sap.formatMessage("flexibility service is not available:\nError message: {0}",E.status);jQuery.sap.log.error(g);return Promise.resolve({changes:{changes:[],contexts:[],variantSection:{},ui2personalization:{}}});});var d=Promise.all([b,e]).then(function(v){var g=v[0];var m=v[1];if(m&&m.changes){if(m.changes.settings&&m.changes.settings.switchedOnBusinessFunctions){m.changes.settings.switchedOnBusinessFunctions.forEach(function(V){C._switches[V]=true;});}m.changes.changes=g.concat(m.changes.changes);}o.file=m;return o.file;},function(g){C._deleteEntry(s,a);throw g;});o.promise=d;C._oFlexDataPromise=f;return d;};C._getChangesFromBundle=function(p){var c=p&&p.appName;if(!c){return Promise.resolve([]);}var r=jQuery.sap.getResourceName(p.appName,"/changes/changes-bundle.json");var b=jQuery.sap.isResourceLoaded(r);if(b){return Promise.resolve(jQuery.sap.loadResource(r));}else{if(!sap.ui.getCore().getConfiguration().getDebug()){return Promise.resolve([]);}try{return Promise.resolve(jQuery.sap.loadResource(r));}catch(e){jQuery.sap.log.warning("flexibility did not find a changesBundle.json  for the application");return Promise.resolve([]);}}};C.NOTAG="<NoTag>";C.getCacheKey=function(c){if(!c||!c.name||!c.appVersion){jQuery.sap.log.warning("Not all parameters were passed to determine a flexibility cache key.");return Promise.resolve(C.NOTAG);}return this.getChangesFillingCache(L.createConnector(),c).then(function(w){if(w&&w.etag){return w.etag;}else{return C.NOTAG;}});};C._getChangeArray=function(c){var s=c.name;var a=c.appVersion||U.DEFAULT_APP_VERSION;var e=C.getEntry(s,a);return e.file.changes.changes;};C.addChange=function(c,o){var a=C._getChangeArray(c);if(!a){return;}a.push(o);};C.updateChange=function(c,o){var a=C._getChangeArray(c);if(!a){return;}for(var i=0;i<a.length;i++){if(a[i].fileName===o.fileName){a.splice(i,1,o);break;}}};C.deleteChange=function(c,o){var a=C._getChangeArray(c);if(!a){return;}for(var i=0;i<a.length;i++){if(a[i].fileName===o.fileName){a.splice(i,1);break;}}};C.getPersonalization=function(r,a,c,i){var m={name:r,appVersion:a};return this.getChangesFillingCache(L.createConnector(),m).then(function(R){if(!R||!R.changes||!R.changes.ui2personalization||!R.changes.ui2personalization[c]){return i?undefined:[];}if(!i){return R.changes.ui2personalization[c]||[];}return R.changes.ui2personalization[c].filter(function(e){return e.itemName===i;})[0];});};C.setPersonalization=function(p){if(!p||!p.reference||!p.containerKey||!p.itemName||!p.content){return Promise.reject("not all mandatory properties were provided for the storage of the personalization");}return L.createConnector().send("/sap/bc/lrep/ui2personalization/","PUT",p,{}).then(this._addPersonalizationToEntries.bind(this,p));};C._addPersonalizationToEntries=function(p){Object.keys(this._entries[p.reference]).forEach(function(v){var e=this._entries[p.reference][v];var P=e.file.changes.ui2personalization;if(!P[p.containerKey]){P[p.containerKey]=[];}P[p.containerKey].push(p);}.bind(this));};C.deletePersonalization=function(r,c,i){if(!r||!c||!i){return Promise.reject("not all mandatory properties were provided for the storage of the personalization");}var u="/sap/bc/lrep/ui2personalization/?reference=";u+=r+"&containerkey="+c+"&itemname="+i;return L.createConnector().send(u,"DELETE",{}).then(this._removePersonalizationFromEntries.bind(this,r,c,i));};C._removePersonalizationFromEntries=function(r,c,i){var d=[];Object.keys(this._entries[r]).forEach(function(a){var g=this.getPersonalization(r,a,c);var G=this.getPersonalization(r,a,c,i);var D=Promise.all([g,G]).then(function(p){var I=p[0];var t=p[1];var n=I.indexOf(t);I.splice(n,1);});d.push(D);}.bind(this));return Promise.all(d);};return C;},true);