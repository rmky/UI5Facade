/*!
 * UI development toolkit for HTML5 (OpenUI5)
 * (c) Copyright 2009-2018 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/layout/cssgrid/GridLayoutBase","sap/ui/layout/cssgrid/GridSettings"],function(G,a){"use strict";var b=G.extend("sap.ui.layout.cssgrid.GridBoxLayout",{metadata:{library:"sap.ui.layout",properties:{boxMinWidth:{type:"sap.ui.core.CSSSize",defaultValue:"10rem"},boxWidth:{type:"sap.ui.core.CSSSize",defaultValue:""}}}});b.prototype.getActiveGridSettings=function(){return new a({gridTemplateColumns:this._getTemplateColumns(),gridGap:"0.5rem 0.5rem",gridAutoRows:"1fr"});};b.prototype._getTemplateColumns=function(){var t="";if(this.getBoxWidth()){t="repeat(auto-fit, "+this.getBoxWidth()+")";}else if(this.getBoxMinWidth()){t="repeat(auto-fit, minmax("+this.getBoxMinWidth()+", 1fr))";}return t;};return b;});
