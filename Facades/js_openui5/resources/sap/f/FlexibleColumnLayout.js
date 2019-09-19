/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["sap/ui/thirdparty/jquery","./library","sap/ui/Device","sap/ui/core/ResizeHandler","sap/ui/core/Control","sap/m/library","sap/m/Button","sap/m/NavContainer","sap/ui/core/Configuration","./FlexibleColumnLayoutRenderer","sap/base/assert"],function(q,l,D,R,C,m,B,N,a,F,b){"use strict";var L=l.LayoutType;var c=C.extend("sap.f.FlexibleColumnLayout",{metadata:{properties:{layout:{type:"sap.f.LayoutType",defaultValue:L.OneColumn},defaultTransitionNameBeginColumn:{type:"string",group:"Appearance",defaultValue:"slide"},defaultTransitionNameMidColumn:{type:"string",group:"Appearance",defaultValue:"slide"},defaultTransitionNameEndColumn:{type:"string",group:"Appearance",defaultValue:"slide"},backgroundDesign:{type:"sap.m.BackgroundDesign",group:"Appearance",defaultValue:m.BackgroundDesign.Transparent}},aggregations:{beginColumnPages:{type:"sap.ui.core.Control",multiple:true,forwarding:{getter:"_getBeginColumn",aggregation:"pages"}},midColumnPages:{type:"sap.ui.core.Control",multiple:true,forwarding:{getter:"_getMidColumn",aggregation:"pages"}},endColumnPages:{type:"sap.ui.core.Control",multiple:true,forwarding:{getter:"_getEndColumn",aggregation:"pages"}},_beginColumnNav:{type:"sap.m.NavContainer",multiple:false,visibility:"hidden"},_midColumnNav:{type:"sap.m.NavContainer",multiple:false,visibility:"hidden"},_endColumnNav:{type:"sap.m.NavContainer",multiple:false,visibility:"hidden"},_beginColumnBackArrow:{type:"sap.m.Button",multiple:false,visibility:"hidden"},_midColumnForwardArrow:{type:"sap.m.Button",multiple:false,visibility:"hidden"},_midColumnBackArrow:{type:"sap.m.Button",multiple:false,visibility:"hidden"},_endColumnForwardArrow:{type:"sap.m.Button",multiple:false,visibility:"hidden"}},associations:{initialBeginColumnPage:{type:"sap.ui.core.Control",multiple:false},initialMidColumnPage:{type:"sap.ui.core.Control",multiple:false},initialEndColumnPage:{type:"sap.ui.core.Control",multiple:false}},events:{stateChange:{parameters:{layout:{type:"sap.f.LayoutType"},maxColumnsCount:{type:"int"},isNavigationArrow:{type:"boolean"},isResize:{type:"boolean"}}},beginColumnNavigate:{allowPreventDefault:true,parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}},afterBeginColumnNavigate:{parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}},midColumnNavigate:{allowPreventDefault:true,parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}},afterMidColumnNavigate:{parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}},endColumnNavigate:{allowPreventDefault:true,parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}},afterEndColumnNavigate:{parameters:{from:{type:"sap.ui.core.Control"},fromId:{type:"string"},to:{type:"sap.ui.core.Control"},toId:{type:"string"},firstTime:{type:"boolean"},isTo:{type:"boolean"},isBack:{type:"boolean"},isBackToTop:{type:"boolean"},isBackToPage:{type:"boolean"},direction:{type:"string"}}}}}});c.COLUMN_RESIZING_ANIMATION_DURATION=560;c.PINNED_COLUMN_CLASS_NAME="sapFFCLPinnedColumn";c.prototype.init=function(){this._initNavContainers();this._initButtons();this._oLayoutHistory=new d();this._oRenderedColumnPagesBoolMap={};};c.prototype._onNavContainerRendered=function(e){var o=e.srcControl,h=o.getPages().length>0,H=this._hasAnyColumnPagesRendered();this._setColumnPagesRendered(o.getId(),h);if(this._hasAnyColumnPagesRendered()!==H){this._hideShowArrows();}};c.prototype._createNavContainer=function(s){var e=s.charAt(0).toUpperCase()+s.slice(1);var n=new N(this.getId()+"-"+s+"ColumnNav",{navigate:function(E){this._handleNavigationEvent(E,false,s);}.bind(this),afterNavigate:function(E){this._handleNavigationEvent(E,true,s);}.bind(this),defaultTransitionName:this["getDefaultTransitionName"+e+"Column"]()});n.addDelegate({"onAfterRendering":this._onNavContainerRendered},this);return n;};c.prototype._handleNavigationEvent=function(e,A,s){var E,f;if(A){E="after"+(s.charAt(0).toUpperCase()+s.slice(1))+"ColumnNavigate";}else{E=s+"ColumnNavigate";}f=this.fireEvent(E,e.mParameters,true);if(!f){e.preventDefault();}};c.prototype._getBeginColumn=function(){return this.getAggregation("_beginColumnNav");};c.prototype._getMidColumn=function(){return this.getAggregation("_midColumnNav");};c.prototype._getEndColumn=function(){return this.getAggregation("_endColumnNav");};c.prototype._flushColumnContent=function(s){var o=this.getAggregation("_"+s+"ColumnNav"),r=sap.ui.getCore().createRenderManager();r.renderControl(o);r.flush(this._$columns[s].find(".sapFFCLColumnContent")[0],undefined,true);r.destroy();};c.prototype.setLayout=function(n){n=this.validateProperty("layout",n);var s=this.getLayout();if(s===n){return this;}var r=this.setProperty("layout",n,true);this._oLayoutHistory.addEntry(n);this._resizeColumns();this._hideShowArrows();return r;};c.prototype.onBeforeRendering=function(){this._deregisterResizeHandler();};c.prototype.onAfterRendering=function(){this._registerResizeHandler();this._cacheDOMElements();this._hideShowArrows();this._resizeColumns();this._flushColumnContent("begin");this._flushColumnContent("mid");this._flushColumnContent("end");this._fireStateChange(false,false);};c.prototype._getControlWidth=function(){return this.$().width();};c.prototype.exit=function(){this._oRenderedColumnPagesBoolMap=null;this._deregisterResizeHandler();this._handleEvent(q.Event("Destroy"));};c.prototype._registerResizeHandler=function(){b(!this._iResizeHandlerId,"Resize handler already registered");this._iResizeHandlerId=R.register(this,this._onResize.bind(this));};c.prototype._deregisterResizeHandler=function(){if(this._iResizeHandlerId){R.deregister(this._iResizeHandlerId);this._iResizeHandlerId=null;}};c.prototype._initNavContainers=function(){this.setAggregation("_beginColumnNav",this._createNavContainer("begin"),true);this.setAggregation("_midColumnNav",this._createNavContainer("mid"),true);this.setAggregation("_endColumnNav",this._createNavContainer("end"),true);};c.prototype._initButtons=function(){var o=new B(this.getId()+"-beginBack",{icon:"sap-icon://slim-arrow-left",tooltip:c._getResourceBundle().getText("FCL_BEGIN_COLUMN_BACK_ARROW"),press:this._onArrowClick.bind(this,"left")}).addStyleClass("sapFFCLNavigationButton").addStyleClass("sapFFCLNavigationButtonRight");this.setAggregation("_beginColumnBackArrow",o,true);var M=new B(this.getId()+"-midForward",{icon:"sap-icon://slim-arrow-right",tooltip:c._getResourceBundle().getText("FCL_MID_COLUMN_FORWARD_ARROW"),press:this._onArrowClick.bind(this,"right")}).addStyleClass("sapFFCLNavigationButton").addStyleClass("sapFFCLNavigationButtonLeft");this.setAggregation("_midColumnForwardArrow",M,true);var e=new B(this.getId()+"-midBack",{icon:"sap-icon://slim-arrow-left",tooltip:c._getResourceBundle().getText("FCL_MID_COLUMN_BACK_ARROW"),press:this._onArrowClick.bind(this,"left")}).addStyleClass("sapFFCLNavigationButton").addStyleClass("sapFFCLNavigationButtonRight");this.setAggregation("_midColumnBackArrow",e,true);var E=new B(this.getId()+"-endForward",{icon:"sap-icon://slim-arrow-right",tooltip:c._getResourceBundle().getText("FCL_END_COLUMN_FORWARD_ARROW"),press:this._onArrowClick.bind(this,"right")}).addStyleClass("sapFFCLNavigationButton").addStyleClass("sapFFCLNavigationButtonLeft");this.setAggregation("_endColumnForwardArrow",E,true);};c.prototype._cacheDOMElements=function(){this._cacheColumns();if(!D.system.phone){this._cacheArrows();}};c.prototype._cacheColumns=function(){this._$columns={begin:this.$("beginColumn"),mid:this.$("midColumn"),end:this.$("endColumn")};};c.prototype._cacheArrows=function(){this._$columnButtons={beginBack:this.$("beginBack"),midForward:this.$("midForward"),midBack:this.$("midBack"),endForward:this.$("endForward")};};c.prototype._getVisibleColumnsCount=function(){return["begin","mid","end"].filter(function(s){return this._getColumnSize(s)>0;},this).length;};c.prototype._resizeColumns=function(){var p,t,A,n=false,e=["begin","mid","end"],r=sap.ui.getCore().getConfiguration().getRTL(),h=sap.ui.getCore().getConfiguration().getAnimationMode()!==a.AnimationMode.none,f,v,i,s,g;if(!this.isActive()){return;}v=this._getVisibleColumnsCount();if(v===0){return;}s=this.getLayout();i=this._getMaxColumnsCountForLayout(s,c.DESKTOP_BREAKPOINT);g=e[i-1];t=(v-1)*c.COLUMN_MARGIN;A=this._getControlWidth()-t;if(h){e.forEach(function(j){var S=this._shouldConcealColumn(i,j),k=this._shouldRevealColumn(i,j===g),o=this._$columns[j];o.toggleClass(c.PINNED_COLUMN_CLASS_NAME,S||k);},this);}e.forEach(function(j){var o=this._$columns[j],k,u,S;p=this._getColumnSize(j);S=h&&this._shouldConcealColumn(i,j);o.toggleClass("sapFFCLColumnMargin",n&&p>0);if(!S){o.toggleClass("sapFFCLColumnActive",p>0);}o.removeClass("sapFFCLColumnHidden");o.removeClass("sapFFCLColumnOnlyActive");o.removeClass("sapFFCLColumnLastActive");o.removeClass("sapFFCLColumnFirstActive");k=Math.round(A*(p/100));if([100,0].indexOf(p)!==-1){u=p+"%";}else{u=k+"px";}if(h){var w=o.get(0);if(o._iResumeResizeHandlerTimeout){clearTimeout(o._iResumeResizeHandlerTimeout);}R.suspend(w);o._iResumeResizeHandlerTimeout=setTimeout(function(){if(S){o.width(u);o.toggleClass("sapFFCLColumnActive",false);}R.resume(w);o._iResumeResizeHandlerTimeout=null;o.toggleClass(c.PINNED_COLUMN_CLASS_NAME,false);this._adjustColumnDisplay(o,k);}.bind(this),c.COLUMN_RESIZING_ANIMATION_DURATION);}else{this._adjustColumnDisplay(o,k);}if(!S){o.width(u);}if(!D.system.phone){this._updateColumnContextualSettings(j,k);this._updateColumnCSSClasses(j,k);}if(p>0){n=true;}},this);f=e.filter(function(j){return this._getColumnSize(j)>0;},this);if(r){e.reverse();}if(f.length===1){this._$columns[f[0]].addClass("sapFFCLColumnOnlyActive");}if(f.length>1){this._$columns[f[0]].addClass("sapFFCLColumnFirstActive");this._$columns[f[f.length-1]].addClass("sapFFCLColumnLastActive");}this._storePreviousResizingInfo(i,g);};c.prototype._adjustColumnDisplay=function(o,n){if(n===0){o.addClass("sapFFCLColumnHidden");}};c.prototype._storePreviousResizingInfo=function(v,s){var o=this.getLayout();this._iPreviousVisibleColumnsCount=v;this._bWasFullScreen=o===L.MidColumnFullScreen||o===L.EndColumnFullScreen;this._sPreviuosLastVisibleColumn=s;};c.prototype._shouldRevealColumn=function(v,i){return(v>this._iPreviousVisibleColumnsCount)&&!this._bWasFullScreen&&i;};c.prototype._shouldConcealColumn=function(v,s){return(v<this._iPreviousVisibleColumnsCount&&s===this._sPreviuosLastVisibleColumn&&!this._bWasFullScreen&&this._getColumnSize(s)===0);};c.prototype._propagateContextualSettings=function(){};c.prototype._updateColumnContextualSettings=function(s,w){var o,e;o=this.getAggregation("_"+s+"ColumnNav");if(!o){return;}e=o._getContextualSettings();if(!e||e.contextualWidth!==w){o._applyContextualSettings({contextualWidth:w});}};c.prototype._updateColumnCSSClasses=function(s,w){var n="";this._$columns[s].removeClass("sapUiContainer-Narrow sapUiContainer-Medium sapUiContainer-Wide sapUiContainer-ExtraWide");if(w<D.media._predefinedRangeSets[D.media.RANGESETS.SAP_STANDARD_EXTENDED].points[0]){n="Narrow";}else if(w<D.media._predefinedRangeSets[D.media.RANGESETS.SAP_STANDARD_EXTENDED].points[1]){n="Medium";}else if(w<D.media._predefinedRangeSets[D.media.RANGESETS.SAP_STANDARD_EXTENDED].points[2]){n="Wide";}else{n="ExtraWide";}this._$columns[s].addClass("sapUiContainer-"+n);};c.prototype._getColumnSize=function(s){var e=this.getLayout(),f=this._getColumnWidthDistributionForLayout(e),S=f.split("/"),M={begin:0,mid:1,end:2},g=S[M[s]];return parseInt(g);};c.prototype.getMaxColumnsCount=function(){return this._getMaxColumnsCountForWidth(this._getControlWidth());};c.prototype._getMaxColumnsCountForWidth=function(w){if(w>=c.DESKTOP_BREAKPOINT){return 3;}if(w>=c.TABLET_BREAKPOINT&&w<c.DESKTOP_BREAKPOINT){return 2;}if(w>0){return 1;}return 0;};c.prototype._getMaxColumnsCountForLayout=function(s,w){var i=this._getMaxColumnsCountForWidth(w),e=this._getColumnWidthDistributionForLayout(s,false,i),S=e.split("/"),M={begin:0,mid:1,end:2},f,g,h=0;Object.keys(M).forEach(function(j){f=S[M[j]];g=parseInt(f);if(g){h++;}});return h;};c.prototype._onResize=function(e){var o=e.oldSize.width,n=e.size.width,O,M;if(n===0){return;}O=this._getMaxColumnsCountForWidth(o);M=this._getMaxColumnsCountForWidth(n);this._resizeColumns();if(M!==O){this._hideShowArrows();this._fireStateChange(false,true);}};c.prototype._setColumnPagesRendered=function(i,h){this._oRenderedColumnPagesBoolMap[i]=h;};c.prototype._hasAnyColumnPagesRendered=function(){return Object.keys(this._oRenderedColumnPagesBoolMap).some(function(k){return this._oRenderedColumnPagesBoolMap[k];},this);};c.prototype._onArrowClick=function(s){var e=this.getLayout(),i=typeof c.SHIFT_TARGETS[e]!=="undefined"&&typeof c.SHIFT_TARGETS[e][s]!=="undefined",n;b(i,"An invalid layout was used for determining arrow behavior");n=i?c.SHIFT_TARGETS[e][s]:L.OneColumn;this.setLayout(n);if(c.ARROWS_NAMES[n][s]!==c.ARROWS_NAMES[e][s]&&i){var o=s==='right'?'left':'right';this._$columnButtons[c.ARROWS_NAMES[n][o]].focus();}this._fireStateChange(true,false);};c.prototype._hideShowArrows=function(){var s=this.getLayout(),M={},n=[],i,I;if(!this.isActive()||D.system.phone){return;}i=this.getMaxColumnsCount();if(i>1){M[L.TwoColumnsBeginExpanded]=["beginBack"];M[L.TwoColumnsMidExpanded]=["midForward"];M[L.ThreeColumnsMidExpanded]=["midForward","midBack"];M[L.ThreeColumnsEndExpanded]=["endForward"];M[L.ThreeColumnsMidExpandedEndHidden]=["midForward","midBack"];M[L.ThreeColumnsBeginExpandedEndHidden]=["beginBack"];if(typeof M[s]==="object"){n=M[s];}}I=this._hasAnyColumnPagesRendered();Object.keys(this._$columnButtons).forEach(function(k){this._toggleButton(k,I&&n.indexOf(k)!==-1);},this);};c.prototype._toggleButton=function(s,S){this._$columnButtons[s].toggle(S);};c.prototype._fireStateChange=function(i,I){if(this._getControlWidth()===0){return;}this.fireStateChange({isNavigationArrow:i,isResize:I,layout:this.getLayout(),maxColumnsCount:this.getMaxColumnsCount()});};c.prototype.setInitialBeginColumnPage=function(p){this._getBeginColumn().setInitialPage(p);this.setAssociation('initialBeginColumnPage',p,true);return this;};c.prototype.setInitialMidColumnPage=function(p){this._getMidColumn().setInitialPage(p);this.setAssociation('initialMidColumnPage',p,true);return this;};c.prototype.setInitialEndColumnPage=function(p){this._getEndColumn().setInitialPage(p);this.setAssociation('initialEndColumnPage',p,true);return this;};c.prototype.to=function(p,t,o,T){if(this._getBeginColumn().getPage(p)){this._getBeginColumn().to(p,t,o,T);}else if(this._getMidColumn().getPage(p)){this._getMidColumn().to(p,t,o,T);}else{this._getEndColumn().to(p,t,o,T);}return this;};c.prototype.backToPage=function(p,o,t){if(this._getBeginColumn().getPage(p)){this._getBeginColumn().backToPage(p,o,t);}else if(this._getMidColumn().getPage(p)){this._getMidColumn().backToPage(p,o,t);}else{this._getEndColumn().backToPage(p,o,t);}return this;};c.prototype._safeBackToPage=function(p,t,e,T){if(this._getBeginColumn().getPage(p)){this._getBeginColumn()._safeBackToPage(p,t,e,T);}else if(this._getMidColumn().getPage(p)){this._getMidColumn()._safeBackToPage(p,t,e,T);}else{this._getEndColumn()._safeBackToPage(p,t,e,T);}};c.prototype.toBeginColumnPage=function(p,t,o,T){this._getBeginColumn().to(p,t,o,T);return this;};c.prototype.toMidColumnPage=function(p,t,o,T){this._getMidColumn().to(p,t,o,T);return this;};c.prototype.toEndColumnPage=function(p,t,o,T){this._getEndColumn().to(p,t,o,T);return this;};c.prototype.backBeginColumn=function(e,t){return this._getBeginColumn().back(e,t);};c.prototype.backMidColumn=function(e,t){return this._getMidColumn().back(e,t);};c.prototype.backEndColumn=function(e,t){return this._getEndColumn().back(e,t);};c.prototype.backBeginColumnToPage=function(p,e,t){return this._getBeginColumn().backToPage(p,e,t);};c.prototype.backMidColumnToPage=function(p,e,t){return this._getMidColumn().backToPage(p,e,t);};c.prototype.backEndColumnToPage=function(p,e,t){return this._getEndColumn().backToPage(p,e,t);};c.prototype.backToTopBeginColumn=function(o,t){this._getBeginColumn().backToTop(o,t);return this;};c.prototype.backToTopMidColumn=function(o,t){this._getMidColumn().backToTop(o,t);return this;};c.prototype.backToTopEndColumn=function(o,t){this._getEndColumn().backToTop(o,t);return this;};c.prototype.getCurrentBeginColumnPage=function(){return this._getBeginColumn().getCurrentPage();};c.prototype.getCurrentMidColumnPage=function(){return this._getMidColumn().getCurrentPage();};c.prototype.getCurrentEndColumnPage=function(){return this._getEndColumn().getCurrentPage();};c.prototype.setDefaultTransitionNameBeginColumn=function(t){this.setProperty("defaultTransitionNameBeginColumn",t,true);this._getBeginColumn().setDefaultTransitionName(t);return this;};c.prototype.setDefaultTransitionNameMidColumn=function(t){this.setProperty("defaultTransitionNameMidColumn",t,true);this._getMidColumn().setDefaultTransitionName(t);return this;};c.prototype.setDefaultTransitionNameEndColumn=function(t){this.setProperty("defaultTransitionNameEndColumn",t,true);this._getEndColumn().setDefaultTransitionName(t);return this;};c.prototype._getLayoutHistory=function(){return this._oLayoutHistory;};c.prototype._getColumnWidthDistributionForLayout=function(s,A,M){var o={},r;M||(M=this.getMaxColumnsCount());if(M===0){r="0/0/0";}else{o[L.OneColumn]="100/0/0";o[L.MidColumnFullScreen]="0/100/0";o[L.EndColumnFullScreen]="0/0/100";if(M===1){o[L.TwoColumnsBeginExpanded]="0/100/0";o[L.TwoColumnsMidExpanded]="0/100/0";o[L.ThreeColumnsMidExpanded]="0/0/100";o[L.ThreeColumnsEndExpanded]="0/0/100";o[L.ThreeColumnsMidExpandedEndHidden]="0/0/100";o[L.ThreeColumnsBeginExpandedEndHidden]="0/0/100";}else{o[L.TwoColumnsBeginExpanded]="67/33/0";o[L.TwoColumnsMidExpanded]="33/67/0";o[L.ThreeColumnsMidExpanded]=M===2?"0/67/33":"25/50/25";o[L.ThreeColumnsEndExpanded]=M===2?"0/33/67":"25/25/50";o[L.ThreeColumnsMidExpandedEndHidden]="33/67/0";o[L.ThreeColumnsBeginExpandedEndHidden]="67/33/0";}r=o[s];}if(A){r=r.split("/").map(function(e){return parseInt(e);});}return r;};c.COLUMN_MARGIN=8;c.DESKTOP_BREAKPOINT=1280;c.TABLET_BREAKPOINT=960;c.ARROWS_NAMES={TwoColumnsBeginExpanded:{"left":"beginBack"},TwoColumnsMidExpanded:{"right":"midForward"},ThreeColumnsMidExpanded:{"left":"midBack","right":"midForward"},ThreeColumnsEndExpanded:{"right":"endForward"},ThreeColumnsMidExpandedEndHidden:{"left":"midBack","right":"midForward"},ThreeColumnsBeginExpandedEndHidden:{"left":"beginBack"}};c._getResourceBundle=function(){return sap.ui.getCore().getLibraryResourceBundle("sap.f");};c.SHIFT_TARGETS={TwoColumnsBeginExpanded:{"left":L.TwoColumnsMidExpanded},TwoColumnsMidExpanded:{"right":L.TwoColumnsBeginExpanded},ThreeColumnsMidExpanded:{"left":L.ThreeColumnsEndExpanded,"right":L.ThreeColumnsMidExpandedEndHidden},ThreeColumnsEndExpanded:{"right":L.ThreeColumnsMidExpanded},ThreeColumnsMidExpandedEndHidden:{"left":L.ThreeColumnsMidExpanded,"right":L.ThreeColumnsBeginExpandedEndHidden},ThreeColumnsBeginExpandedEndHidden:{"left":L.ThreeColumnsMidExpandedEndHidden}};function d(){this._aLayoutHistory=[];}d.prototype.addEntry=function(s){if(typeof s!=="undefined"){this._aLayoutHistory.push(s);}};d.prototype.getClosestEntryThatMatches=function(e){var i;for(i=this._aLayoutHistory.length-1;i>=0;i--){if(e.indexOf(this._aLayoutHistory[i])!==-1){return this._aLayoutHistory[i];}}};return c;});
