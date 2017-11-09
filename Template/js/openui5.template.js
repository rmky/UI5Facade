/**
 * oApp is represents the app on the current page. 
 * 
 * It contains a shell, a list of views and methods to add/remove or show views. Each view has
 * a unique id and may contain OpenUI5 components for contents, the right menu, the shell header
 * and the shell curtain. Thus, the state of the shell may change with every view.
 * 
 * How to use:
 * 
 * // Start the app
 * oApp.shell.placeAt("myElementId");
 * oApp.addView("viewId", [oSomething, oSomethingElse], [oMyNavMenu]);
 * oApp.ShowView("viewId");
 * 
 * // Change view (new view will be added automatically, the menu will not change!)
 * oApp.ShowView({
 * 		id: "viewId2",
 * 		contents: [oSomething, oSomethingElse],
 * });
 * 
 */
var oApp = {
	shell: new sap.ui.unified.Shell({
		icon: "exface/vendor/exface/OpenUI5Template/Template/images/sap_50x26.png"
	}),
	addView: function (id, content, menu, headerItemsLeft, headerItemsRight, curtain, curtainPane) {
		oApp._views[id] = {id: id};
		if (content) {
			oApp._views[id].content = content;
		}
		if (menu) {
			oApp._views[id].menu = menu;
			if (! headerItemsLeft) {
				headerItemsLeft = [
					menuItm
				]
			}
		}
		if (headerItemsLeft) {
			oApp._views[id].headerItemsLeft = headerItemsLeft;
		}
		if (content) {
			oApp._views[id].content = content;
		}
		if (curtain) {
			oApp._views[id].curtain = curtain;
		}
		if (curtainPane) {
			oApp._views[id].curtainPane = curtainPane;
		}
	},
	removeView: function(id) {
		delete oApp._views['id'];
	},
	getView: function(id) {
		return oApp._views[id];
	},
	showView: function(idOrObject) {
		if (typeof idOrObject === 'object') {
			if (idOrObject.id === undefined) {
				throw "Cannot show view: view has no id defined!";
			}
			oApp.addView(idOrObject.id, idOrObject.contents, idOrObject.menu, idOrObject.headerItemsLeft, idOrObject.headerItemsRight, idOrObject.curtain, idOrObject.curtainPane);
			oApp._applyView(idOrObject)
		} else {
			oApp._applyView(oApp.getView(idOrObject));
		}
	},
	getFirstView: function() {
		return oApp._views[Object.keys(oApp._views)[0]];
	},
	_currentViewId: '',
	_views: {
		/* Structure:
		{
			"id" : {
				menu: [objects for oApp.shell.addPaneContent()],
				headerItemsLeft: [menuItm, homeItm, ...],
				headerItemsRight: [logoffItm, ...],
				curtain: [objects for oApp.shell.addCurtainContent()],
				curtainPane: [objects for oApp.shell.addCurtainPaneContent()]
				content: [objects for oApp.shell.addContent()]
			}
		}
		*/
	},
	_applyView: function (view){
		if(view.menu){
			oApp.shell.removeAllPaneContent();
			for(var i=0; i<view.menu.length; i++){
				oApp.shell.addPaneContent(view.menu[i]);
			}
		}
		if(view.curtainPane){
			oApp.shell.removeAllCurtainPaneContent();
			for(var i=0; i<view.curtainPane.length; i++){
				oApp.shell.addCurtainPaneContent(view.curtainPane[i]);
			}
		}
		if(view.curtain){
			oApp.shell.removeAllCurtainContent();
			for(var i=0; i<view.curtain.length; i++){
				oApp.shell.addCurtainContent(view.curtain[i]);
			}
		}
		if(view.content){
			oApp.shell.removeAllContent();
			for(var i=0; i<view.content.length; i++){
				oApp.shell.addContent(view.content[i]);
			}
		}
		if(view.headerItemsLeft){
			oApp.shell.removeAllHeadItems();
			for(var i=0; i<view.headerItemsLeft.length; i++){
				oApp.shell.addHeadItem(view.headerItemsLeft[i]);
			}
		}
		if(view.headerItemsRight){
			oApp.shell.removeAllHeadEndItems();
			for(var i=0; i<view.headerItemsRight.length; i++){
				oApp.shell.addHeadEndItem(view.headerItemsRight[i]);
			}
		}
	}
}

// Shell items. They can be referenced in states to quickly setup what the shee shows
var menuItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Configuration",
	icon: sap.ui.core.IconPool.getIconURI("menu2"),
	press: function(){
		oApp.shell.setShowPane(!oApp.shell.getShowPane());
	}
});
var curtainConfigItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Configuration",
	icon: sap.ui.core.IconPool.getIconURI("menu2"),
	showMarker: true,
	press: function(){
		oApp.shell.setShowCurtainPane(!oApp.shell.getShowCurtainPane());
		curtainConfigItm.setSelected(!curtainConfigItm.getSelected());
		curtainConfigItm.setShowMarker(!curtainConfigItm.getShowMarker());
		sap.ui.getCore().byId("CurtainContent").setHeaderHidden(oApp.shell.getShowCurtainPane());
	}
});
var homeItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Home",
	icon: sap.ui.core.IconPool.getIconURI("home"),
	press: function(){alert('Home pressed')},
	ariaLabelledBy: ["homeItm-txt"]
});
var closeItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Close",
	icon: sap.ui.core.IconPool.getIconURI("decline"),
	press: function(){
		oApp.shell.setShowCurtain(false);
		setState(oldState);
	}
});
var logoffItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Logoff",
	icon: sap.ui.core.IconPool.getIconURI("log"),
	toggleEnabled: false,
	press: function(){
	}
});
// END shell items