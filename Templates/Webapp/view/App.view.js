sap.ui.jsview("[#app_id#].view.App", {

	/** Specifies the Controller belonging to this View. 
	* In the case that it is not implemented, or that "null" is returned, this View does not have a Controller.
	* @memberOf view.View1
	*/ 
	getControllerName : function() {
		return "[#app_id#].controller.App";
	},

	/** Is initially called once after the Controller has been instantiated. It is the place where the UI is constructed. 
	* Since the Controller is given to this method, its event handlers can be attached right away. 
	* @memberOf view.View1
	*/ 
	createContent : function(oController) {
	    
	    return new [#ui5_app_control#]("[#app_id#].app", { // use sap.m.NavContainer instead of App to get rid of the title bar
			pages: [
				new sap.m.Page({
					content: [
						
					]
				})
			]
		});
	}
});