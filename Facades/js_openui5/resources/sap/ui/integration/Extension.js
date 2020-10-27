/*!
 * OpenUI5
 * (c) Copyright 2009-2020 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/base/ManagedObject"],function(M){"use strict";var E=M.extend("sap.ui.integration.Extension",{metadata:{library:"sap.ui.integration",properties:{actions:{type:"sap.ui.integration.CardMenuAction[]"},formatters:{type:"object"}},events:{action:{allowPreventDefault:true,parameters:{card:{type:"sap.ui.core.Control"},actionConfig:{type:'object'},actionSource:{type:"sap.ui.core.Control"},parameters:{type:"object"},type:{type:"sap.ui.integration.CardActionType"}}}}}});E.prototype.init=function(){this._oCard=null;};E.prototype.exit=function(){this._oCard=null;};E.prototype.onCardReady=function(c){this._oCard=c;};E.prototype.getCard=function(){return this._oCard;};return E;});
