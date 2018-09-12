sap.ui.define([
	"sap/ui/core/mvc/Controller",
	"sap/ui/core/routing/History"
], function (Controller, History) {
	"use strict";
	return Controller.extend("[#app_id#].controller.BaseController", {
		
		getRouter : function () {
			return sap.ui.core.UIComponent.getRouterFor(this);
		},
		
		getViewId : function(sViewName) {
			return this.getOwnerComponent().createId(sViewName);
		},
		
		getViewName : function(sPageAlias, sWidgetId) {
			return sPageAlias + (sWidgetId ? '.'+sWidgetId : '');
		},
		
		/**
		 * Navigates to the view matching the given page and widget.
		 * 
		 * Returns the the jQuery XHR object used to load the view or nothing if no request
		 * to the server was made (view loaded from cache).
		 * 
		 * @param String sPageAlias
		 * @param String sWigetId
		 * @param Object oXHRSettings
		 * 
		 * @return jqXHR|undefined
		 */
		navTo : function(sPageAlias, sWidgetId, oXHRSettings) {
			var oRouter = this.getRouter();	
			var sViewName = this.getViewName(sPageAlias, sWidgetId);
			var sViewId = this.getViewId(sViewName);
			
			// Register page in router
			this._addRoute(oRouter, sViewName);
			
			// Load view and controller with a custom async AJAX if running on UI server. 
			// Reasons:
			// 1) By default, views and controllers are loaded with sync requests (not compatible with CacheAPI)
			// 2) Loading a single viewcontroller is faster, than the view and the controller separately
			if (! sap.ui.getCore().byId(sViewId)) {
				if (oXHRSettings) {
					var oCallbacks = {
						success: oXHRSettings.success,
						error: oXHRSettings.error
					}
					delete oXHRSettings.success;
					delete oXHRSettings.error;
				}
				
				var oDefSettings = {
					url: this._getUrlFromRoute(sViewName, 'viewcontroller'),
					dataType: "script",
					cache: true,
					success: function(script, textStatus) {
						console.log("Loaded page " + sViewName + ", timestamp = " + new Date().getTime());
						
						if (oCallbacks && oCallbacks.success) {
							oCallbacks.success();
						}
						
						// TODO this produces the following error: Modules that use an anonymous define() 
						// call must be loaded with a require() call; they must not be executed via script 
						// tag or nested into other modules. All other usages will fail in future releases 
						// or when standard AMD loaders are used or when ui5loader runs in async mode. 
						// Now using substitute name ~anonymous~1.js -  sap.ui.ModuleSystem
						// Obviously, we need to wrap ap.ui.jsview(...) in the view definition file in
						// something - but what???
						oRouter.navTo(sViewName);
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.warn("Failed loading combined viewcontroller for " + sViewName + ": using fallback to native routing.");
						if (oCallbacks && oCallbacks.error) {
							oCallbacks.error();
						}
						
						if (navigator.onLine === false) {
							oRouter.getTargets().display("offline", {
								fromTarget : "home"
							});
						} else {
							oRouter.navTo(sViewName);
						}
					}
				}
				
				return $.ajax($.extend({}, oDefSettings, oXHRSettings));
			} else {
				if (oXHRSettings) {
					if (oXHRSettings.success) {
						oXHRSettings.success();
					}
					if (oXHRSettings.complete) {
						oXHRSettings.complete();
					}
				}
				oRouter.navTo(sViewName);
			}
		},
		
		/**
		 * Adds a target and a corrseponding route to the given router.
		 * 
		 * @private
		 * @param sap.ui.core.routing.Router oRouter
		 * @param String sPattern
		 */
		_addRoute: function(oRouter, sPattern) {
			var aTargets = oRouter.getTargets();
			
			if (aTargets.getTarget(sPattern) === undefined) {
				jQuery.sap.log.info('Adding target ' + sPattern);
				aTargets.addTarget(sPattern, {
					"viewId": sPattern,
					"viewName": sPattern
				});
			}
			
			if (oRouter.getRoute(sPattern) === undefined) {
				jQuery.sap.log.info('Adding route ' + sPattern);
				oRouter.addRoute({
					"pattern": sPattern,
					"name": sPattern,
					"target": sPattern
				});
			}
		},
		
		/**
		 * Computes an API URL to a resource for the given page.
		 * 
		 * @private
		 * 
		 * @param String sViewName
		 * @param String sType (view|controller|viewcontroller)
		 */
		_getUrlFromRoute: function(sViewName, sType) {
			return this._getResourceRoot() + '/' + sType + '/' + sViewName.replace(/\./g, '/') + '.' + sType + '.js';
		},
		
		/**
		 * @private
		 */
		_getResourceRoot: function() {
			return '[#assets_path#]';
		},
		
		onNavBack : function (oEvent) {
			var oHistory, sPreviousHash;
			oHistory = History.getInstance();
			sPreviousHash = oHistory.getPreviousHash();
			if (sPreviousHash !== undefined) {
				window.history.go(-1);
			} else {
				this.getRouter().navTo("appHome", {}, true /*no history*/);
			}
		}
		
	});
});

