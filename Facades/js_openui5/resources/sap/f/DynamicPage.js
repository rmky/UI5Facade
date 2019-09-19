/*!
 * OpenUI5
 * (c) Copyright 2009-2019 SAP SE or an SAP affiliate company.
 * Licensed under the Apache License, Version 2.0 - see LICENSE.txt.
 */
sap.ui.define(["./library","sap/ui/core/Control","sap/m/ScrollBar","sap/m/library","sap/ui/base/ManagedObjectObserver","sap/ui/core/ResizeHandler","sap/ui/core/Configuration","sap/ui/core/delegate/ScrollEnablement","sap/ui/Device","sap/f/DynamicPageTitle","sap/f/DynamicPageHeader","./DynamicPageRenderer","sap/base/Log","sap/ui/dom/getScrollbarSize","sap/ui/core/library"],function(l,C,S,L,M,R,a,b,D,c,d,e,f,g,h){"use strict";var P=L.PageBackgroundDesign;var i=C.extend("sap.f.DynamicPage",{metadata:{library:"sap.f",properties:{preserveHeaderStateOnScroll:{type:"boolean",group:"Behavior",defaultValue:false},headerExpanded:{type:"boolean",group:"Behavior",defaultValue:true},toggleHeaderOnTitleClick:{type:"boolean",group:"Behavior",defaultValue:true},showFooter:{type:"boolean",group:"Behavior",defaultValue:false},backgroundDesign:{type:"sap.m.PageBackgroundDesign",group:"Appearance",defaultValue:P.Standard},fitContent:{type:"boolean",group:"Behavior",defaultValue:false}},associations:{stickySubheaderProvider:{type:"sap.f.IDynamicPageStickyContent",multiple:false}},aggregations:{title:{type:"sap.f.DynamicPageTitle",multiple:false},header:{type:"sap.f.DynamicPageHeader",multiple:false},content:{type:"sap.ui.core.Control",multiple:false},footer:{type:"sap.m.IBar",multiple:false},landmarkInfo:{type:"sap.f.DynamicPageAccessibleLandmarkInfo",multiple:false},_scrollBar:{type:"sap.ui.core.Control",multiple:false,visibility:"hidden"}},dnd:{draggable:false,droppable:true},designtime:"sap/f/designtime/DynamicPage.designtime"}});function j(o){if(arguments.length===1){return o&&("length"in o)?o.length>0:!!o;}return Array.prototype.slice.call(arguments).every(function(O){return j(O);});}function k(E){var o;if(!E){return false;}o=E.getBoundingClientRect();return!!(o.width&&o.height);}var A=h.AccessibleLandmarkRole;i.HEADER_MAX_ALLOWED_PINNED_PERCENTAGE=0.6;i.HEADER_MAX_ALLOWED_NON_SROLLABLE_PERCENTAGE=0.6;i.HEADER_MAX_ALLOWED_NON_SROLLABLE_ON_MOBILE=0.3;i.BREAK_POINTS={TABLET:1024,PHONE:600};i.EVENTS={TITLE_PRESS:"_titlePress",TITLE_MOUSE_OVER:"_titleMouseOver",TITLE_MOUSE_OUT:"_titleMouseOut",PIN_UNPIN_PRESS:"_pinUnpinPress",VISUAL_INDICATOR_MOUSE_OVER:"_visualIndicatorMouseOver",VISUAL_INDICATOR_MOUSE_OUT:"_visualIndicatorMouseOut",HEADER_VISUAL_INDICATOR_PRESS:"_headerVisualIndicatorPress",TITLE_VISUAL_INDICATOR_PRESS:"_titleVisualIndicatorPress"};i.MEDIA={PHONE:"sapFDynamicPage-Std-Phone",TABLET:"sapFDynamicPage-Std-Tablet",DESKTOP:"sapFDynamicPage-Std-Desktop"};i.RESIZE_HANDLER_ID={PAGE:"_sResizeHandlerId",TITLE:"_sTitleResizeHandlerId",HEADER:"_sHeaderResizeHandlerId",CONTENT:"_sContentResizeHandlerId"};i.DIV="div";i.HEADER="header";i.FOOTER="footer";i.SHOW_FOOTER_CLASS_NAME="sapFDynamicPageActualFooterControlShow";i.HIDE_FOOTER_CLASS_NAME="sapFDynamicPageActualFooterControlHide";i.prototype.init=function(){this._bPinned=false;this._bHeaderInTitleArea=false;this._bExpandingWithAClick=false;this._bSuppressToggleHeaderOnce=false;this._headerBiggerThanAllowedHeight=false;this._oStickySubheader=null;this._bStickySubheaderInTitleArea=false;this._bMSBrowser=D.browser.internet_explorer||D.browser.edge||false;this._oScrollHelper=new b(this,this.getId()+"-content",{horizontal:false,vertical:true});this._oHeaderObserver=null;this._oSubHeaderAfterRenderingDelegate={onAfterRendering:this._adjustStickyContent};};i.prototype.onBeforeRendering=function(){if(!this._preserveHeaderStateOnScroll()){this._attachPinPressHandler();}this._attachTitlePressHandler();this._attachVisualIndicatorsPressHandlers();this._attachVisualIndicatorMouseOverHandlers();this._attachTitleMouseOverHandlers();this._addStickySubheaderAfterRenderingDelegate();this._detachScrollHandler();};i.prototype.onAfterRendering=function(){var s,m;if(this._preserveHeaderStateOnScroll()){setTimeout(this._overridePreserveHeaderStateOnScroll.bind(this),0);}this._bPinned=false;this._cacheDomElements();this._detachResizeHandlers();this._attachResizeHandlers();this._updateMedia(this._getWidth(this));this._attachScrollHandler();this._updateScrollBar();this._attachPageChildrenAfterRenderingDelegates();this._resetPinButtonState();if(!this.getHeaderExpanded()){this._snapHeader(false);s=this.getHeader()&&!this.getPreserveHeaderStateOnScroll()&&this._canSnapHeaderOnScroll();if(s){m=this._getScrollBar().getScrollPosition();this._setScrollPosition(m?m:this._getSnappingHeight());}else{this._toggleHeaderVisibility(false);this._moveHeaderToTitleArea();}}this._updateToggleHeaderVisualIndicators();this._updateTitleVisualState();};i.prototype.exit=function(){this._detachResizeHandlers();if(this._oScrollHelper){this._oScrollHelper.destroy();}if(this._oHeaderObserver){this._oHeaderObserver.disconnect();}if(this._oStickySubheader){this._oStickySubheader.removeEventDelegate(this._oSubHeaderAfterRenderingDelegate);}};i.prototype.setShowFooter=function(s){var r=this.setProperty("showFooter",s,true);this._toggleFooter(s);return r;};i.prototype.setHeader=function(H){var o;if(H===o){return;}o=this.getHeader();if(o){if(this._oHeaderObserver){this._oHeaderObserver.disconnect();}this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.HEADER);o.detachEvent(i.EVENTS.PIN_UNPIN_PRESS,this._onPinUnpinButtonPress);this._bAlreadyAttachedPinPressHandler=false;o.detachEvent(i.EVENTS.HEADER_VISUAL_INDICATOR_PRESS,this._onCollapseHeaderVisualIndicatorPress);this._bAlreadyAttachedHeaderIndicatorPressHandler=false;o.detachEvent(i.EVENTS.VISUAL_INDICATOR_MOUSE_OVER,this._onVisualIndicatorMouseOver);o.detachEvent(i.EVENTS.VISUAL_INDICATOR_MOUSE_OUT,this._onVisualIndicatorMouseOut);this._bAlreadyAttachedVisualIndicatorMouseOverOutHandler=false;this._bAlreadyAttachedHeaderObserver=false;}this.setAggregation("header",H);return this;};i.prototype.setStickySubheaderProvider=function(s){var o,O=this.getStickySubheaderProvider();if(s===O){return this;}o=sap.ui.getCore().byId(O);if(this._oStickySubheader&&o){o._returnStickyContent();o._setStickySubheaderSticked(false);this._oStickySubheader.removeEventDelegate(this._oSubHeaderAfterRenderingDelegate);this._bAlreadyAddedStickySubheaderAfterRenderingDelegate=false;this._oStickySubheader=null;}this.setAssociation("stickySubheaderProvider",s);return this;};i.prototype.setHeaderExpanded=function(H){H=this.validateProperty("headerExpanded",H);if(this._bPinned){return this;}if(this.getHeaderExpanded()===H){return this;}if(this.getDomRef()){this._titleExpandCollapseWhenAllowed();}this.setProperty("headerExpanded",H,true);return this;};i.prototype.setToggleHeaderOnTitleClick=function(t){var H=this.getHeaderExpanded(),r=this.setProperty("toggleHeaderOnTitleClick",t,true);t=this.getProperty("toggleHeaderOnTitleClick");this._updateTitleVisualState();this._updateToggleHeaderVisualIndicators();this._updateARIAStates(H);return r;};i.prototype.setFitContent=function(F){var r=this.setProperty("fitContent",F,true);if(j(this.$())){this._updateFitContainer();}return r;};i.prototype.getScrollDelegate=function(){return this._oScrollHelper;};i.prototype._overridePreserveHeaderStateOnScroll=function(){if(!this._shouldOverridePreserveHeaderStateOnScroll()){this._headerBiggerThanAllowedHeight=false;return;}this._headerBiggerThanAllowedHeight=true;if(this.getHeaderExpanded()){this._moveHeaderToContentArea(true);}else{this._adjustSnap();}this._updateScrollBar();};i.prototype._shouldOverridePreserveHeaderStateOnScroll=function(){return!D.system.desktop&&this._headerBiggerThanAllowedToBeFixed()&&this._preserveHeaderStateOnScroll();};i.prototype._toggleFooter=function(s){var F=this.getFooter(),u;if(!j(this.$())||!j(F)||!j(this.$footerWrapper)){return;}u=sap.ui.getCore().getConfiguration().getAnimationMode()!==a.AnimationMode.none;this._toggleFooterSpacer(s);if(u){this._toggleFooterAnimation(s,F);}else{this.$footerWrapper.toggleClass("sapUiHidden",!s);}this._updateScrollBar();};i.prototype._toggleFooterAnimation=function(s,F){this.$footerWrapper.bind("webkitAnimationEnd animationend",this._onToggleFooterAnimationEnd.bind(this,F));if(s){this.$footerWrapper.removeClass("sapUiHidden");}F.toggleStyleClass(i.SHOW_FOOTER_CLASS_NAME,s);F.toggleStyleClass(i.HIDE_FOOTER_CLASS_NAME,!s);};i.prototype._onToggleFooterAnimationEnd=function(F){this.$footerWrapper.unbind("webkitAnimationEnd animationend");if(F.hasStyleClass(i.HIDE_FOOTER_CLASS_NAME)){this.$footerWrapper.addClass("sapUiHidden");F.removeStyleClass(i.HIDE_FOOTER_CLASS_NAME);}else{F.removeStyleClass(i.SHOW_FOOTER_CLASS_NAME);}};i.prototype._toggleFooterSpacer=function(t){var $=this.$("spacer");if(j($)){$.toggleClass("sapFDynamicPageContentWrapperSpacer",t);}if(j(this.$contentFitContainer)){this.$contentFitContainer.toggleClass("sapFDynamicPageContentFitContainerFooterVisible",t);}};i.prototype._toggleHeaderInTabChain=function(t){var o=this.getTitle(),m=this.getHeader();if(!j(o)||!j(m)){return;}m.$().css("visibility",t?"visible":"hidden");};i.prototype._snapHeader=function(m,u){var o=this.getTitle();if(this._bPinned&&!u){f.debug("DynamicPage :: aborted snapping, header is pinned",this);return;}f.debug("DynamicPage :: snapped header",this);if(this._bPinned&&u){this._unPin();this._togglePinButtonPressedState(false);}if(j(o)){o._toggleState(false,u);if(m&&this._bHeaderInTitleArea){this._moveHeaderToContentArea(true);}}if(!j(this.$titleArea)){f.warning("DynamicPage :: couldn't snap header. There's no title.",this);return;}this.setProperty("headerExpanded",false,true);if(this._hasVisibleTitleAndHeader()){this.$titleArea.addClass(D.system.phone&&o.getSnappedTitleOnMobile()?"sapFDynamicPageTitleSnappedTitleOnMobile":"sapFDynamicPageTitleSnapped");this._updateToggleHeaderVisualIndicators();this._togglePinButtonVisibility(false);}this._toggleHeaderInTabChain(false);this._updateARIAStates(false);};i.prototype._expandHeader=function(m,u){var o=this.getTitle();f.debug("DynamicPage :: expand header",this);if(j(o)){o._toggleState(true,u);if(m){this._moveHeaderToTitleArea(true);}}if(!j(this.$titleArea)){f.warning("DynamicPage :: couldn't expand header. There's no title.",this);return;}this.setProperty("headerExpanded",true,true);if(this._hasVisibleTitleAndHeader()){this.$titleArea.removeClass(D.system.phone&&o.getSnappedTitleOnMobile()?"sapFDynamicPageTitleSnappedTitleOnMobile":"sapFDynamicPageTitleSnapped");this._updateToggleHeaderVisualIndicators();if(!this.getPreserveHeaderStateOnScroll()&&!this._headerBiggerThanAllowedToPin()){this._togglePinButtonVisibility(true);}}this._toggleHeaderInTabChain(true);this._updateARIAStates(true);};i.prototype._toggleHeaderVisibility=function(s,u){var E=this.getHeaderExpanded(),o=this.getTitle(),m=this.getHeader();if(this._bPinned&&!u){f.debug("DynamicPage :: header toggle aborted, header is pinned",this);return;}if(j(o)){o._toggleState(E);}if(j(m)){m.$().toggleClass("sapFDynamicPageHeaderHidden",!s);this._updateScrollBar();}};i.prototype._moveHeaderToContentArea=function(o){var m=this.getHeader();if(j(m)){m.$().prependTo(this.$wrapper);this._bHeaderInTitleArea=false;if(o){this._offsetContentOnMoveHeader();}}};i.prototype._moveHeaderToTitleArea=function(o){var m=this.getHeader();if(j(m)){m.$().prependTo(this.$stickyPlaceholder);this._bHeaderInTitleArea=true;if(o){this._offsetContentOnMoveHeader();}}};i.prototype._offsetContentOnMoveHeader=function(){var o=Math.ceil(this._getHeaderHeight()),m=this._getScrollPosition(),n=this._getScrollBar().getScrollPosition(),N;if(!o){return;}if(!m&&n){N=this._getScrollBar().getScrollPosition();}else{N=this._bHeaderInTitleArea?m-o:m+o;}N=Math.max(N,0);this._setScrollPosition(N,true);};i.prototype._pin=function(){var $=this.$();if(this._bPinned){return;}this._bPinned=true;if(!this._bHeaderInTitleArea){this._moveHeaderToTitleArea(true);this._updateScrollBar();}this._updateToggleHeaderVisualIndicators();this._togglePinButtonARIAState(this._bPinned);if(j($)){$.addClass("sapFDynamicPageHeaderPinned");}};i.prototype._unPin=function(){var $=this.$();if(!this._bPinned){return;}this._bPinned=false;this._updateToggleHeaderVisualIndicators();this._togglePinButtonARIAState(this._bPinned);if(j($)){$.removeClass("sapFDynamicPageHeaderPinned");}};i.prototype._togglePinButtonVisibility=function(t){var o=this.getHeader();if(j(o)){o._setShowPinBtn(t);}};i.prototype._togglePinButtonPressedState=function(p){var o=this.getHeader();if(j(o)){o._togglePinButton(p);}};i.prototype._togglePinButtonARIAState=function(p){var o=this.getHeader();if(j(o)){o._updateARIAPinButtonState(p);}};i.prototype._resetPinButtonState=function(){if(this._preserveHeaderStateOnScroll()){this._togglePinButtonVisibility(false);}else{this._togglePinButtonPressedState(false);this._togglePinButtonARIAState(false);}};i.prototype._restorePinButtonFocus=function(){this.getHeader()._focusPinButton();};i.prototype._getScrollPosition=function(){return j(this.$wrapper)?Math.ceil(this.$wrapper.scrollTop()):0;};i.prototype._setScrollPosition=function(n,s){if(!j(this.$wrapper)){return;}if(this._getScrollPosition()===n){return;}if(s){this._bSuppressToggleHeaderOnce=true;}if(!this.getScrollDelegate()._$Container){this.getScrollDelegate()._$Container=this.$wrapper;}this.getScrollDelegate().scrollTo(0,n);};i.prototype._shouldSnapOnScroll=function(){return!this._preserveHeaderStateOnScroll()&&this._getScrollPosition()>=this._getSnappingHeight()&&this.getHeaderExpanded()&&!this._bPinned;};i.prototype._shouldExpandOnScroll=function(){var I=this._needsVerticalScrollBar();return!this._preserveHeaderStateOnScroll()&&this._getScrollPosition()<this._getSnappingHeight()&&!this.getHeaderExpanded()&&!this._bPinned&&I;};i.prototype._shouldStickStickyContent=function(){var I,s,m;m=this._getScrollPosition();I=m<=Math.ceil(this._getHeaderHeight())&&!this._bPinned&&!this.getPreserveHeaderStateOnScroll();s=m===0||I&&this._hasVisibleHeader();return!s;};i.prototype._headerScrolledOut=function(){return this._getScrollPosition()>=this._getSnappingHeight();};i.prototype._headerSnapAllowed=function(){return!this._preserveHeaderStateOnScroll()&&this.getHeaderExpanded()&&!this._bPinned;};i.prototype._canSnapHeaderOnScroll=function(){var m=this._getMaxScrollPosition(),t=this._bMSBrowser?1:0;if(this._bHeaderInTitleArea){m+=this._getHeaderHeight();m-=t;}return m>this._getSnappingHeight();};i.prototype._getSnappingHeight=function(){return Math.ceil(this._getHeaderHeight()||this._getTitleHeight());};i.prototype._getMaxScrollPosition=function(){var $;if(j(this.$wrapper)){$=this.$wrapper[0];return $.scrollHeight-$.clientHeight;}return 0;};i.prototype._needsVerticalScrollBar=function(){var t=this._bMSBrowser?1:0;return this._getMaxScrollPosition()>t;};i.prototype._getOwnHeight=function(){return this._getHeight(this);};i.prototype._getEntireHeaderHeight=function(){var t=0,H=0,o=this.getTitle(),m=this.getHeader();if(j(o)){t=o.$().outerHeight();}if(j(m)){H=m.$().outerHeight();}return t+H;};i.prototype._headerBiggerThanAllowedToPin=function(m){if(!(typeof m==="number"&&!isNaN(parseInt(m)))){m=this._getOwnHeight();}return this._getEntireHeaderHeight()>i.HEADER_MAX_ALLOWED_PINNED_PERCENTAGE*m;};i.prototype._headerBiggerThanAllowedToBeFixed=function(){var m=this._getOwnHeight();return this._getEntireHeaderHeight()>i.HEADER_MAX_ALLOWED_NON_SROLLABLE_PERCENTAGE*m;};i.prototype._headerBiggerThanAllowedToBeExpandedInTitleArea=function(){var E=this._getEntireHeaderHeight(),m=this._getOwnHeight();if(m===0){return false;}return D.system.phone?E>=i.HEADER_MAX_ALLOWED_NON_SROLLABLE_ON_MOBILE*m:E>=m;};i.prototype._measureScrollBarOffsetHeight=function(){var H=0,s=!this.getHeaderExpanded(),m=this._bHeaderInTitleArea;if(this._preserveHeaderStateOnScroll()||this._bPinned||(!s&&this._bHeaderInTitleArea)){H=this._getTitleAreaHeight();f.debug("DynamicPage :: preserveHeaderState is enabled or header pinned :: title area height"+H,this);return H;}if(s||!j(this.getTitle())||!this._canSnapHeaderOnScroll()){H=this._getTitleHeight();f.debug("DynamicPage :: header snapped :: title height "+H,this);return H;}this._snapHeader(true);H=this._getTitleHeight();if(!s){this._expandHeader(m);}f.debug("DynamicPage :: snapped mode :: title height "+H,this);return H;};i.prototype._updateScrollBar=function(){var s,m,n;if(!D.system.desktop||!j(this.$wrapper)||(this._getHeight(this)===0)){return;}s=this._getScrollBar();s.setContentSize(this._measureScrollBarOffsetHeight()+this.$wrapper[0].scrollHeight+"px");m=this._needsVerticalScrollBar();n=this.bHasScrollbar!==m;if(n){s.toggleStyleClass("sapUiHidden",!m);this.toggleStyleClass("sapFDynamicPageWithScroll",m);this.bHasScrollbar=m;}setTimeout(this._updateFitContainer.bind(this),0);setTimeout(this._updateScrollBarOffset.bind(this),0);};i.prototype._updateFitContainer=function(n){var N=typeof n!=='undefined'?!n:!this._needsVerticalScrollBar(),F=this.getFitContent(),t=F||N;this.$contentFitContainer.toggleClass("sapFDynamicPageContentFitContainer",t);};i.prototype._updateScrollBarOffset=function(){var s=sap.ui.getCore().getConfiguration().getRTL()?"left":"right",o=this._needsVerticalScrollBar()?g().width+"px":0,F=this.getFooter();this.$titleArea.css("padding-"+s,o);if(j(F)){F.$().css(s,o);}};i.prototype._updateHeaderARIAState=function(E){var o=this.getHeader();if(j(o)){o._updateARIAState(E);}};i.prototype._updateTitleARIAState=function(E){var o=this.getTitle();if(j(o)){o._updateARIAState(E);}};i.prototype._updateARIAStates=function(E){this._updateHeaderARIAState(E);this._updateTitleARIAState(E);};i.prototype._updateMedia=function(w){if(w<=i.BREAK_POINTS.PHONE){this._updateMediaStyle(i.MEDIA.PHONE);}else if(w<=i.BREAK_POINTS.TABLET){this._updateMediaStyle(i.MEDIA.TABLET);}else{this._updateMediaStyle(i.MEDIA.DESKTOP);}};i.prototype._updateMediaStyle=function(s){Object.keys(i.MEDIA).forEach(function(m){var E=s===i.MEDIA[m];this.toggleStyleClass(i.MEDIA[m],E);},this);};i.prototype._toggleExpandVisualIndicator=function(t){var o=this.getTitle();if(j(o)){o._toggleExpandButton(t);}};i.prototype._focusExpandVisualIndicator=function(){var o=this.getTitle();if(j(o)){o._focusExpandButton();}};i.prototype._toggleCollapseVisualIndicator=function(t){var o=this.getHeader();if(j(o)){o._toggleCollapseButton(t);}};i.prototype._focusCollapseVisualIndicator=function(){var o=this.getHeader();if(j(o)){o._focusCollapseButton();}};i.prototype._updateToggleHeaderVisualIndicators=function(){var H,m,E,n=this._hasVisibleTitleAndHeader();if(!this.getToggleHeaderOnTitleClick()||!n){m=false;E=false;}else{H=this.getHeaderExpanded();m=H;E=D.system.phone&&this.getTitle().getAggregation("snappedTitleOnMobile")?false:!H;}this._toggleCollapseVisualIndicator(m);this._toggleExpandVisualIndicator(E);};i.prototype._updateTitleVisualState=function(){var t=this.getTitle(),T=this._hasVisibleTitleAndHeader()&&this.getToggleHeaderOnTitleClick();this.$().toggleClass("sapFDynamicPageTitleClickEnabled",T&&!D.system.phone);if(j(t)){t._toggleFocusableState(T);}};i.prototype._scrollBellowCollapseVisualIndicator=function(){var H=this.getHeader(),$,m,v,o;if(j(H)){$=this.getHeader()._getCollapseButton().getDomRef();m=$.getBoundingClientRect().height;v=this.$wrapper[0].getBoundingClientRect().height;o=$.offsetTop+m-v;this._setScrollPosition(o);}};i.prototype._hasVisibleTitleAndHeader=function(){var t=this.getTitle();return j(t)&&t.getVisible()&&this._hasVisibleHeader();};i.prototype._hasVisibleHeader=function(){var H=this.getHeader();return j(H)&&H.getVisible()&&j(H.getContent());};i.prototype._getHeight=function(o){var $;if(!(o instanceof C)){return 0;}$=o.getDomRef();return $?$.getBoundingClientRect().height:0;};i.prototype._getWidth=function(o){return!(o instanceof C)?0:o.$().outerWidth()||0;};i.prototype._getTitleAreaHeight=function(){return j(this.$titleArea)?this.$titleArea.outerHeight()||0:0;};i.prototype._getTitleHeight=function(){return this._getHeight(this.getTitle());};i.prototype._getHeaderHeight=function(){return this._getHeight(this.getHeader());};i.prototype._preserveHeaderStateOnScroll=function(){return this.getPreserveHeaderStateOnScroll()&&!this._headerBiggerThanAllowedHeight;};i.prototype._getScrollBar=function(){if(!j(this.getAggregation("_scrollBar"))){var v=new S(this.getId()+"-vertSB",{scrollPosition:0,scroll:this._onScrollBarScroll.bind(this)});this.setAggregation("_scrollBar",v,true);}return this.getAggregation("_scrollBar");};i.prototype._cacheDomElements=function(){var F=this.getFooter();if(j(F)){this.$footer=F.$();this.$footerWrapper=this.$("footerWrapper");}this.$wrapper=this.$("contentWrapper");this.$contentFitContainer=this.$("contentFitContainer");this.$titleArea=this.$("header");this.$stickyPlaceholder=this.$("stickyPlaceholder");this._cacheTitleDom();this._cacheHeaderDom();};i.prototype._cacheTitleDom=function(){var t=this.getTitle();if(j(t)){this.$title=t.$();}};i.prototype._cacheHeaderDom=function(){var H=this.getHeader();if(j(H)){this.$header=H.$();}};i.prototype._adjustSnap=function(){var o,I,m,n,s,p,$=this.$();if(!j($)){return;}if(!k($[0])){return;}o=this.getHeader();I=!this.getHeaderExpanded();if(!o||!I){return;}m=!this._preserveHeaderStateOnScroll()&&this._canSnapHeaderOnScroll();n=I&&o.$().hasClass("sapFDynamicPageHeaderHidden");if(m&&n){this._toggleHeaderVisibility(true);this._moveHeaderToContentArea(true);return;}if(!m&&!n){this._moveHeaderToTitleArea(true);this._toggleHeaderVisibility(false);return;}if(m){s=this._getScrollPosition();p=this._getSnappingHeight();if(s<p){this._setScrollPosition(p);}}};i.prototype.ontouchmove=function(E){E.setMarked();};i.prototype._onChildControlAfterRendering=function(E){var s=E.srcControl;if(s instanceof c){this._cacheTitleDom();this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.TITLE);this._registerResizeHandler(i.RESIZE_HANDLER_ID.TITLE,this.$title[0],this._onChildControlsHeightChange.bind(this));}else if(s instanceof d){this._cacheHeaderDom();this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.HEADER);this._registerResizeHandler(i.RESIZE_HANDLER_ID.HEADER,this.$header[0],this._onChildControlsHeightChange.bind(this));}setTimeout(this._updateScrollBar.bind(this),0);};i.prototype._onChildControlsHeightChange=function(){var n=this._needsVerticalScrollBar();if(n){this._updateFitContainer(n);}this._adjustSnap();if(!this._bExpandingWithAClick){this._updateScrollBar();}this._bExpandingWithAClick=false;};i.prototype._onResize=function(E){var o=this.getTitle(),m=this.getHeader(),n=E.size.width;if(!this._preserveHeaderStateOnScroll()&&m){if(this._headerBiggerThanAllowedToPin(E.size.height)||D.system.phone){this._unPin();this._togglePinButtonVisibility(false);this._togglePinButtonPressedState(false);}else{this._togglePinButtonVisibility(true);}if(this.getHeaderExpanded()&&this._bHeaderInTitleArea&&this._headerBiggerThanAllowedToBeExpandedInTitleArea()){this._expandHeader(false);this._setScrollPosition(0);}}if(j(o)){o._onResize(n);}this._adjustSnap();this._updateScrollBar();this._updateMedia(n);};i.prototype._onWrapperScroll=function(E){var s=Math.max(E.target.scrollTop,0);if(D.system.desktop){if(this.allowCustomScroll===true){this.allowCustomScroll=false;return;}this.allowInnerDiv=true;this._getScrollBar().setScrollPosition(s);this.toggleStyleClass("sapFDynamicPageWithScroll",this._needsVerticalScrollBar());}};i.prototype._toggleHeaderOnScroll=function(){this._adjustStickyContent();if(this._bSuppressToggleHeaderOnce){this._bSuppressToggleHeaderOnce=false;return;}if(D.system.desktop&&this._bExpandingWithAClick){return;}if(this._preserveHeaderStateOnScroll()){return;}if(this._shouldSnapOnScroll()){this._snapHeader(true,true);}else if(this._shouldExpandOnScroll()){this._expandHeader(false,true);this._toggleHeaderVisibility(true);}else if(!this._bPinned&&this._bHeaderInTitleArea){var m=(this._getScrollPosition()>=this._getSnappingHeight());this._moveHeaderToContentArea(m);}};i.prototype._adjustStickyContent=function(){if(!this._oStickySubheader){return;}var o,s=this._shouldStickStickyContent(),m,n=this.getStickySubheaderProvider();if(s===this._bStickySubheaderInTitleArea){return;}m=sap.ui.getCore().byId(n);if(!j(m)){return;}o=document.activeElement;m._setStickySubheaderSticked(s);if(s){this._oStickySubheader.$().appendTo(this.$stickyPlaceholder);}else{m._returnStickyContent();}o.focus();this._bStickySubheaderInTitleArea=s;};i.prototype._onScrollBarScroll=function(){if(this.allowInnerDiv===true){this.allowInnerDiv=false;return;}this.allowCustomScroll=true;this._setScrollPosition(this._getScrollBar().getScrollPosition());};i.prototype._onTitlePress=function(){if(this.getToggleHeaderOnTitleClick()&&this._hasVisibleTitleAndHeader()){this._titleExpandCollapseWhenAllowed(true);this.getTitle()._focus();}};i.prototype._onExpandHeaderVisualIndicatorPress=function(){this._onTitlePress();if(this._headerBiggerThanAllowedToBeExpandedInTitleArea()){this._scrollBellowCollapseVisualIndicator();}this._focusCollapseVisualIndicator();};i.prototype._onCollapseHeaderVisualIndicatorPress=function(){this._onTitlePress();this._focusExpandVisualIndicator();};i.prototype._onVisualIndicatorMouseOver=function(){var $=this.$();if(j($)){$.addClass("sapFDynamicPageTitleForceHovered");}};i.prototype._onVisualIndicatorMouseOut=function(){var $=this.$();if(j($)){$.removeClass("sapFDynamicPageTitleForceHovered");}};i.prototype._onTitleMouseOver=i.prototype._onVisualIndicatorMouseOver;i.prototype._onTitleMouseOut=i.prototype._onVisualIndicatorMouseOut;i.prototype._titleExpandCollapseWhenAllowed=function(u){var m;if(this._bPinned&&!u){return this;}if(this._preserveHeaderStateOnScroll()||!this._canSnapHeaderOnScroll()||!this.getHeader()){if(!this.getHeaderExpanded()){this._expandHeader(false,u);this._toggleHeaderVisibility(true,u);}else{this._snapHeader(false,u);this._toggleHeaderVisibility(false,u);}}else if(!this.getHeaderExpanded()){m=!this._headerBiggerThanAllowedToBeExpandedInTitleArea();this._bExpandingWithAClick=true;this._expandHeader(m,u);this.getHeader().$().removeClass("sapFDynamicPageHeaderHidden");if(!m){this._setScrollPosition(0);}this._bExpandingWithAClick=false;}else{var n=this._bHeaderInTitleArea;this._snapHeader(n,u);if(!n){this._setScrollPosition(this._getSnappingHeight());}}};i.prototype._onPinUnpinButtonPress=function(){if(this._bPinned){this._unPin();}else{this._pin();this._restorePinButtonFocus();}};i.prototype._attachResizeHandlers=function(){var m=this._onChildControlsHeightChange.bind(this);this._registerResizeHandler(i.RESIZE_HANDLER_ID.PAGE,this,this._onResize.bind(this));if(j(this.$title)){this._registerResizeHandler(i.RESIZE_HANDLER_ID.TITLE,this.$title[0],m);}if(j(this.$header)){this._registerResizeHandler(i.RESIZE_HANDLER_ID.HEADER,this.$header[0],m);}if(j(this.$contentFitContainer)){this._registerResizeHandler(i.RESIZE_HANDLER_ID.CONTENT,this.$contentFitContainer[0],m);}};i.prototype._registerResizeHandler=function(H,o,m){if(!this[H]){this[H]=R.register(o,m);}};i.prototype._detachResizeHandlers=function(){this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.PAGE);this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.TITLE);this._deRegisterResizeHandler(i.RESIZE_HANDLER_ID.CONTENT);};i.prototype._deRegisterResizeHandler=function(H){if(this[H]){R.deregister(this[H]);this[H]=null;}};i.prototype._attachPageChildrenAfterRenderingDelegates=function(){var t=this.getTitle(),H=this.getHeader(),o=this.getContent(),p={onAfterRendering:this._onChildControlAfterRendering.bind(this)};if(j(t)){t.addEventDelegate(p);}if(j(o)){o.addEventDelegate(p);}if(j(H)){H.addEventDelegate(p);}};i.prototype._attachTitlePressHandler=function(){var t=this.getTitle();if(j(t)&&!this._bAlreadyAttachedTitlePressHandler){t.attachEvent(i.EVENTS.TITLE_PRESS,this._onTitlePress,this);this._bAlreadyAttachedTitlePressHandler=true;}};i.prototype._attachPinPressHandler=function(){var H=this.getHeader();if(j(H)&&!this._bAlreadyAttachedPinPressHandler){H.attachEvent(i.EVENTS.PIN_UNPIN_PRESS,this._onPinUnpinButtonPress,this);this._bAlreadyAttachedPinPressHandler=true;}};i.prototype._attachHeaderObserver=function(){var H=this.getHeader();if(j(H)&&!this._bAlreadyAttachedHeaderObserver){if(!this._oHeaderObserver){this._oHeaderObserver=new M(this._adjustStickyContent.bind(this));}this._oHeaderObserver.observe(H,{properties:["visible"]});this._bAlreadyAttachedHeaderObserver=true;}};i.prototype._attachVisualIndicatorsPressHandlers=function(){var t=this.getTitle(),H=this.getHeader();if(j(t)&&!this._bAlreadyAttachedTitleIndicatorPressHandler){t.attachEvent(i.EVENTS.TITLE_VISUAL_INDICATOR_PRESS,this._onExpandHeaderVisualIndicatorPress,this);this._bAlreadyAttachedTitleIndicatorPressHandler=true;}if(j(H)&&!this._bAlreadyAttachedHeaderIndicatorPressHandler){H.attachEvent(i.EVENTS.HEADER_VISUAL_INDICATOR_PRESS,this._onCollapseHeaderVisualIndicatorPress,this);this._bAlreadyAttachedHeaderIndicatorPressHandler=true;}};i.prototype._addStickySubheaderAfterRenderingDelegate=function(){var s,m=this.getStickySubheaderProvider(),I;s=sap.ui.getCore().byId(m);if(j(s)&&!this._bAlreadyAddedStickySubheaderAfterRenderingDelegate){I=s.getMetadata().getInterfaces().indexOf("sap.f.IDynamicPageStickyContent")!==-1;if(I){this._oStickySubheader=s._getStickyContent();this._oStickySubheader.addEventDelegate(this._oSubHeaderAfterRenderingDelegate,this);this._bAlreadyAddedStickySubheaderAfterRenderingDelegate=true;this._attachHeaderObserver();}}};i.prototype._attachVisualIndicatorMouseOverHandlers=function(){var H=this.getHeader();if(j(H)&&!this._bAlreadyAttachedVisualIndicatorMouseOverOutHandler){H.attachEvent(i.EVENTS.VISUAL_INDICATOR_MOUSE_OVER,this._onVisualIndicatorMouseOver,this);H.attachEvent(i.EVENTS.VISUAL_INDICATOR_MOUSE_OUT,this._onVisualIndicatorMouseOut,this);this._bAlreadyAttachedVisualIndicatorMouseOverOutHandler=true;}};i.prototype._attachTitleMouseOverHandlers=function(){var t=this.getTitle();if(j(t)&&!this._bAlreadyAttachedTitleMouseOverOutHandler){t.attachEvent(i.EVENTS.TITLE_MOUSE_OVER,this._onTitleMouseOver,this);t.attachEvent(i.EVENTS.TITLE_MOUSE_OUT,this._onTitleMouseOut,this);this._bAlreadyAttachedTitleMouseOverOutHandler=true;}};i.prototype._attachScrollHandler=function(){this._onWrapperScrollReference=this._onWrapperScroll.bind(this);this._toggleHeaderOnScrollReference=this._toggleHeaderOnScroll.bind(this);this.$wrapper.on("scroll",this._onWrapperScrollReference);this.$wrapper.on("scroll",this._toggleHeaderOnScrollReference);};i.prototype._detachScrollHandler=function(){if(this.$wrapper){this.$wrapper.off("scroll",this._onWrapperScrollReference);this.$wrapper.off("scroll",this._toggleHeaderOnScrollReference);}};i.prototype._formatLandmarkInfo=function(o,p){if(o){var r=o["get"+p+"Role"]()||"",s=o["get"+p+"Label"]()||"";if(r===A.None){r='';}return{role:r.toLowerCase(),label:s};}return{};};i.prototype._getHeaderTag=function(o){if(o&&o.getHeaderRole()!==A.None){return i.DIV;}return i.HEADER;};i.prototype._getFooterTag=function(o){if(o&&o.getFooterRole()!==A.None){return i.DIV;}return i.FOOTER;};return i;});
