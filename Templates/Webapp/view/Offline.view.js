sap.ui.jsview("[#app_id#].view.Offline", {

	/** Specifies the Controller belonging to this View. 
	* In the case that it is not implemented, or that "null" is returned, this View does not have a Controller.
	* @memberOf view.View1
	*/ 
	getControllerName : function() {
		return "[#app_id#].controller.Offline";
	},

	/** Is initially called once after the Controller has been instantiated. It is the place where the UI is constructed. 
	* Since the Controller is given to this method, its event handlers can be attached right away. 
	* @memberOf view.View1
	*/ 
	createContent : function(oController) {
	    
	    return new sap.m.MessagePage({
			title: "{i18n>Offline}",
			icon: "sap-icon://disconnected",
			text: "{i18n>Offline.text}",
			description: "{i18n>Offline.description}",
			showNavButton: true,
			navButtonPress: [oController.onNavBack, oController]
		});
	}
});