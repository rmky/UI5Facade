/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(['sap/ui/base/ManagedObject','sap/ui/core/Control','sap/ui/core/mvc/Controller','sap/base/util/merge','sap/ui/core/library',"./ViewRenderer","sap/base/assert","sap/base/Log","sap/ui/thirdparty/jquery"],function(M,C,a,m,b,V,c,L,q){"use strict";var d=b.mvc.ViewType;var e=C.extend("sap.ui.core.mvc.View",{metadata:{interfaces:["sap.ui.core.IDScope"],library:"sap.ui.core",properties:{width:{type:"sap.ui.core.CSSSize",group:"Dimension",defaultValue:'100%'},height:{type:"sap.ui.core.CSSSize",group:"Dimension",defaultValue:null},viewName:{type:"string",group:"Misc",defaultValue:null},displayBlock:{type:"boolean",group:"Appearance",defaultValue:false}},aggregations:{content:{type:"sap.ui.core.Control",multiple:true,singularName:"content"}},events:{afterInit:{},beforeExit:{},afterRendering:{},beforeRendering:{}},specialSettings:{controller:'sap.ui.core.mvc.Controller',controllerName:'string',preprocessors:'Object',resourceBundleName:'string',resourceBundleUrl:'sap.ui.core.URI',resourceBundleLocale:'string',resourceBundleAlias:'string',type:'string',definition:'any',viewContent:{type:'any',deprecated:true},viewData:'any',async:{type:"boolean",defaultValue:false}},designtime:"sap/ui/core/designtime/mvc/View.designtime"}});e._mPreprocessors={};function f(P){P._settings={};for(var i in P){if(i.indexOf("_")!==0){P._settings[i]=P[i];}}}function g(P,A){var i;if(typeof P.preprocessor==="string"){var j=P.preprocessor.replace(/\./g,"/");if(A){return new Promise(function(l,w){sap.ui.require([j],function(i){l(i);});});}else{return sap.ui.requireSync(j);}}else if(typeof P.preprocessor==="function"&&!P.preprocessor.process){i={process:P.preprocessor};}else{i=P.preprocessor;}if(A){return Promise.resolve(i);}else{return i;}}function h(j,T){var w=this.mPreprocessors[T]||[],G=[],i,l,O,P=[];if(e._mPreprocessors[j]&&e._mPreprocessors[j][T]){G=e._mPreprocessors[j][T].map(function(x){return q.extend({},x);});}for(i=0,l=G.length;i<l;i++){if(G[i]._onDemand){O=G[i];}else{P.push(G[i]);}}for(i=0,l=w.length;i<l;i++){var I=!w[i].preprocessor;if(I&&O){P.unshift(q.extend(w[i],O));}else if(!I){P.push(w[i]);}}return P;}function k(i,S){var j=i.getMetadata().getClass();function l(P){P.preprocessor=g(P,S.async);}i.mPreprocessors=q.extend({},S.preprocessors);for(var _ in j.PreprocessorType){var T=j.PreprocessorType[_];if(i.mPreprocessors[T]&&!Array.isArray(i.mPreprocessors[T])){i.mPreprocessors[T]=[i.mPreprocessors[T]];}else if(!i.mPreprocessors[T]){i.mPreprocessors[T]=[];}i.mPreprocessors[T].forEach(f);i.mPreprocessors[T]=h.call(i,j._sType,T);i.mPreprocessors[T].forEach(l);}}function n(i){i.oAsyncState={};i.oAsyncState.promise=null;}var o=function(T,S){if(!sap.ui.getCore().getConfiguration().getControllerCodeDeactivated()){var i=S.controller,N=i&&typeof i.getMetadata==="function"&&i.getMetadata().getName(),A=S.async;if(!i&&T.getControllerName){var j=T.getControllerName();if(j){var l=sap.ui.require('sap/ui/core/CustomizingConfiguration');var w=l&&l.getControllerReplacement(j,M._sOwnerId);if(w){j=typeof w==="string"?w:w.controllerName;}if(A){i=a.create({name:j});}else{i=sap.ui.controller(j,true);}}}else if(i){var O=M._sOwnerId;if(!i._isExtended()){if(A){i=a.extendByCustomizing(i,N,A).then(function(i){return a.extendByProvider(i,N,O,A);});}else{i=a.extendByCustomizing(i,N,A);i=a.extendByProvider(i,N,O,A);}}else if(A){i=Promise.resolve(i);}}if(i){var x=function(i){T.oController=i;i.oView=T;};if(A){if(!T.oAsyncState){throw new Error("The view "+T.sViewName+" runs in sync mode and therefore cannot use async controller extensions!");}return i.then(x);}else{x(i);}}}else{sap.ui.controller("sap.ui.core.mvc.EmptyControllerImpl",{"_sap.ui.core.mvc.EmptyControllerImpl":true});T.oController=sap.ui.controller("sap.ui.core.mvc.EmptyControllerImpl");}};e.prototype._initCompositeSupport=function(S){c(!S.preprocessors||this.getMetadata().getName().indexOf("XMLView"),"Preprocessors only available for XMLView");this.oViewData=S.viewData;this.sViewName=S.viewName;var i=this;k(this,S);if(S.async){n(this);}var j=sap.ui.require('sap/ui/core/CustomizingConfiguration');if(j&&j.hasCustomProperties(this.sViewName,this)){this._fnSettingsPreprocessor=function(S){var I=this.getId();if(j&&I){if(i.isPrefixedId(I)){I=I.substring((i.getId()+"--").length);}var l=j.getCustomProperties(i.sViewName,I,i);if(l){S=q.extend(S,l);}}};}var P=function(l,w){c(typeof l==="function","fn must be a function");var x=sap.ui.require("sap/ui/core/Component");var O=x&&x.getOwnerComponentFor(i);if(O){if(w){i.fnScopedRunWithOwner=i.fnScopedRunWithOwner||function(y){return O.runAsOwner(y);};}return O.runAsOwner(l);}return l();};var A=function(l){if(l.oController&&l.oController.connectToView){return l.oController.connectToView(l);}};var F=function(){if(i.onControllerConnected){return i.onControllerConnected(i.oController);}};if(this.initViewSettings){if(S.async){this.oAsyncState.promise=this.initViewSettings(S).then(function(){return P(o.bind(null,i,S),true);}).then(function(){return P(F,true);}).then(function(){return A(i);}).then(function(){return i.runPreprocessor("controls",i,false);}).then(function(){return P(i.fireAfterInit.bind(i),true);}).then(function(){return i;});}else{this.initViewSettings(S);o(this,S);F();A(this);this.runPreprocessor("controls",this,true);this.fireAfterInit();}}};e.prototype.getController=function(){return this.oController;};e.prototype.byId=function(i){return sap.ui.getCore().byId(this.createId(i));};e.prototype.createId=function(i){if(!this.isPrefixedId(i)){i=this.getId()+"--"+i;}return i;};e.prototype.getLocalId=function(i){var P=this.getId()+"--";return(i&&i.indexOf(P)===0)?i.slice(P.length):null;};e.prototype.isPrefixedId=function(i){return!!(i&&i.indexOf(this.getId()+"--")===0);};e.prototype.getViewData=function(){return this.oViewData;};function p(){this.oAsyncState=null;}e.prototype.exit=function(){this.fireBeforeExit();delete this.oController;delete this.oPreprocessorInfo;if(this.oAsyncState){var D=p.bind(this);this.oAsyncState.promise.then(D,D);}};e.prototype.onAfterRendering=function(){this.fireAfterRendering();};e.prototype.onBeforeRendering=function(){this.fireBeforeRendering();};e.prototype.clone=function(i,l){var S={},K,w;for(K in this.mProperties&&!(this.isBound&&this.isBound(K))){if(this.mProperties.hasOwnProperty(K)){S[K]=this.mProperties[K];}}w=C.prototype.clone.call(this,i,l,{cloneChildren:false,cloneBindings:true});var E,x,j;for(E in w.mEventRegistry){x=w.mEventRegistry[E];for(j=x.length-1;j>=0;j--){if(x[j].oListener===this.getController()){x[j]={oListener:w.getController(),fFunction:x[j].fFunction,oData:x[j].oData};}}}w.applySettings(S);return w;};e.prototype.getPreprocessors=function(){return this.mPreprocessors;};e.prototype.getPreprocessorInfo=function(S){if(!this.oPreprocessorInfo){this.oPreprocessorInfo={name:this.sViewName,componentId:this._sOwnerId,id:this.getId(),caller:this+" ("+this.sViewName+")",sync:!!S};}if(e._supportInfo){this.oPreprocessorInfo._supportInfo=e._supportInfo;}return this.oPreprocessorInfo;};e.prototype.runPreprocessor=function(T,S,j){var w=this.getPreprocessorInfo(j),P=this.mPreprocessors&&this.mPreprocessors[T]||[],x,A,y;if(!j){A=function(w,z){return function(S){return z.preprocessor.then(function(B){return B.process(S,w,z._settings);});};};y=Promise.resolve(S);}for(var i=0,l=P.length;i<l;i++){if(j&&P[i]._syncSupport===true){x=P[i].preprocessor.process;S=x(S,w,P[i]._settings);}else if(!j){y=y.then(A(w,P[i]));}else{L.debug("Async \""+T+"\"-preprocessor was skipped in sync view execution for "+this.getMetadata().getClass()._sType+"View",this.getId());}}return j?S:y;};function r(T,i){if(!e._mPreprocessors[i]){e._mPreprocessors[i]={};}if(!e._mPreprocessors[i][T]){e._mPreprocessors[i][T]=[];}}function s(i,j,T){e._mPreprocessors[j][T].forEach(function(P){if(P._onDemand){L.error("Registration for \""+T+"\" failed, only one on-demand-preprocessor allowed",i.getMetadata().getName());return false;}});return true;}e.registerPreprocessor=function(T,P,i,S,O,j){if(typeof O!=="boolean"){j=O;O=false;}if(P){r(T,i);if(O&&!s(this,i,T)){return;}e._mPreprocessors[i][T].push({preprocessor:P,_onDemand:O,_syncSupport:S,_settings:j});L.debug("Registered "+(O?"on-demand-":"")+"preprocessor for \""+T+"\""+(S?" with syncSupport":""),this.getMetadata().getName());}else{L.error("Registration for \""+T+"\" failed, no preprocessor specified",this.getMetadata().getName());}};e.prototype.hasPreprocessor=function(T){return!!this.mPreprocessors[T].length;};e.create=function(O){var P=m({},O);P.async=true;P.viewContent=P.definition;var i=sap.ui.require("sap/ui/core/Component");var j;if(i&&M._sOwnerId){j=i.get(M._sOwnerId);}function u(){return v(P.id,P,P.type).loaded();}return new Promise(function(l,w){var x=t(P);sap.ui.require([x],function(y){l(y);},function(E){w(E);});}).then(function(l){if(j){return j.runAsOwner(u);}else{return u();}});};e._legacyCreate=v;sap.ui.view=function(i,j,T){var l=function(w){var N="";if(typeof i=="object"){N=i.viewName;}N=N||(j&&j.name);L[w]("Do not use deprecated view factory functions ("+N+")."+"Use the static create function on the view module instead: [XML|JS|HTML|JSON|]View.create().","sap.ui.view",null,function(){return{type:"sap.ui.view",name:N};});};if(j&&j.async){l("info");}else{l("warning");}return v(i,j,T);};function v(i,j,T){var l=null,w={};if(typeof i==="object"||typeof i==="string"&&j===undefined){j=i;i=undefined;}if(j){if(typeof j==="string"){w.viewName=j;}else{w=j;}}c(!w.async||typeof w.async==="boolean","sap.ui.view factory: Special setting async has to be of the type 'boolean'!");if(i){w.id=i;}if(T){w.type=T;}var x=sap.ui.require('sap/ui/core/CustomizingConfiguration');if(x){var y=x.getViewReplacement(w.viewName,M._sOwnerId);if(y){L.info("Customizing: View replacement for view '"+w.viewName+"' found and applied: "+y.viewName+" (type: "+y.type+")");q.extend(w,y);}else{L.debug("Customizing: no View replacement found for view '"+w.viewName+"'.");}}var z=t(w);l=u(z,w);return l;}function t(i){var j;if(!i.type){throw new Error("No view type specified.");}else if(i.type===d.JS){j='sap/ui/core/mvc/JSView';}else if(i.type===d.JSON){j='sap/ui/core/mvc/JSONView';}else if(i.type===d.XML){j='sap/ui/core/mvc/XMLView';}else if(i.type===d.HTML){j='sap/ui/core/mvc/HTMLView';}else if(i.type===d.Template){j='sap/ui/core/mvc/TemplateView';}else{throw new Error("Unknown view type "+i.type+" specified.");}return j;}function u(i,j){var l=sap.ui.require(i);if(!l){l=sap.ui.requireSync(i);if(j.async){L.warning("sap.ui.view was called without requiring the according view class.");}}return new l(j);}e.prototype.loaded=function(){if(this.oAsyncState&&this.oAsyncState.promise){return this.oAsyncState.promise;}else{return Promise.resolve(this);}};return e;});