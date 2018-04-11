sap.ui.jsview("[#app_id#].view.NotFound", {

	/** Specifies the Controller belonging to this View. 
	* In the case that it is not implemented, or that "null" is returned, this View does not have a Controller.
	* @memberOf view.View1
	*/ 
	getControllerName : function() {
		return "[#app_id#].controller.NotFound";
	},

	/** Is initially called once after the Controller has been instantiated. It is the place where the UI is constructed. 
	* Since the Controller is given to this method, its event handlers can be attached right away. 
	* @memberOf view.View1
	*/ 
	createContent : function(oController) {
	    
	    return new sap.m.MessagePage({
			title: "{i18n>NotFound}",
			text: "{i18n>NotFound.text}",
			description: "{i18n>NotFound.description}",
			showNavButton: true,
			navButtonPress: [oController.onNavBack, oController]
		});
	}
});