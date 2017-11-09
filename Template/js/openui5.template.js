/**
 * oApp is represents the app on the current page. 
 * 
 * It contains a shell, a list of views and methods to add/remove or show views. Each view has
 * a unique id and may contain OpenUI5 components for contents, the right menu, and the shell 
 * header. Thus, the state of the shell may change with every view.
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
	addView: function (id, content, menu, headerItemsLeft, headerItemsRight) {
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
			oApp.addView(idOrObject.id, idOrObject.contents, idOrObject.menu, idOrObject.headerItemsLeft, idOrObject.headerItemsRight);
			oApp._applyView(idOrObject)
		} else {
			oApp._applyView(oApp.getView(idOrObject));
		}
	},
	getCurrentView: function() {
		return oApp.getView(oApp._currentViewId);
	},
	getFirstView: function() {
		return oApp._views[Object.keys(oApp._views)[0]];
	},
	getPreviousView: function() {
		return oApp.getView(oApp._previousViewId);
	},
	_previousViewId: '',
	_currentViewId: '',
	_views: {
		/* Structure:
		{
			"id" : {
				menu: [objects for oApp.shell.addPaneContent()],
				headerItemsLeft: [menuItm, homeItm, ...],
				headerItemsRight: [logoffItm, ...]
				content: [objects for oApp.shell.addContent()]
			}
		}
		*/
	},
	_applyView: function (view){
		oApp._previousViewId = oApp._currentViewId;
		oApp._currentViewId = view.id;
		if(view.menu){
			oApp.shell.removeAllPaneContent();
			for(var i=0; i<view.menu.length; i++){
				oApp.shell.addPaneContent(view.menu[i]);
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
		console.log('Changed view to ', view);
	}
}

// Shell items. They can be referenced in states to quickly setup what the shee shows
var menuItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Configuration",
	icon: "sap-icon://menu2",
	press: function(){
		oApp.shell.setShowPane(!oApp.shell.getShowPane());
	}
});
var homeItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Home",
	icon: "sap-icon://home",
	press: function(){alert('Home pressed')},
	ariaLabelledBy: ["homeItm-txt"]
});
var closeItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Close",
	icon: "sap-icon://decline",
	press: function(){
		oApp.showView(oApp.getPreviousView());
	}
});
var logoffItm = new sap.ui.unified.ShellHeadItem({
	tooltip: "Logoff",
	icon: "sap-icon://log",
	toggleEnabled: false,
	press: function(){
	}
});
// END shell items