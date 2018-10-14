// Toggle online/offlie icon
window.addEventListener('online', function(){
	exfLauncher.toggleOnlineIndicator();
});
window.addEventListener('offline', function(){
	exfLauncher.toggleOnlineIndicator();
});

const exfLauncher = {};
(function() {
	
	var _oShell = {};
	var _oLauncher = this;
	
	this.getShell = function() {
		return _oShell;
	};
	
	this.initShell = function() {
		_oShell = new sap.ui.unified.Shell({
			header: [
				new sap.m.OverflowToolbar({
		            design: "Transparent",
					content: [
						new sap.m.Button({
		                    icon: "sap-icon://menu2",
		                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
		                    press: function() {
		                    	oShell.setShowPane(! oShell.getShowPane());
		            		}
		                }),
		                new sap.m.Image({
							src: "exface/vendor/exface/OpenUI5Template/Templates/images/sap_50x26.png",
							height: "26px",
							width: "50px",
							densityAware: false,
							//visible: ! sap.ui.Device.system.phone
		                }),
		                new sap.m.ToolbarSpacer(),
		                new sap.m.Label("exf_pagetitle", {
		                    text: "",
		                    design: "Bold",
		                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
		                }),
		                new sap.m.ToolbarSpacer(),
		                new sap.m.Button("exf-connection-indicator", {
		                    icon: function(){return navigator.onLine ? "sap-icon://connected" : "sap-icon://disconnected"}(),
		                    text: "3/1",
		                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
		                    press: function(oEvent){
								var oButton = oEvent.getSource();
								var oPopover = new sap.m.Popover({
									title: "{= ${/_online} > 0 ? ${i18n>/WEBAPP.SHELL.STATE.ONLINE} : ${i18n>/WEBAPP.SHELL.STATE.OFFLINE} }",
									placement: "Bottom",
									content: [
										new sap.m.List({
											items: [
												new sap.m.StandardListItem({
													title: "Sync-Puffer (0)",
													type: "Active",
													press: function(){
														var oData = {
																data: [
																	/*{
																		"action_alias": "exface.Core.CreateData",
																		"caption": "Speichern",
																		"object_alias": "alexa.RMS-demo.BBD_ALERT",
																		"object_name": "MHD-Alarm",
																		"triggered": "2017-02-05 13:55:37"
																	},
																	{
																		"action_alias": "exface.Core.UpdateData",
																		"caption": "Speichern",
																		"object_alias": "axenox.WMS.picking_order_pos",
																		"object_name": "Pickauftragsposition",
																		"triggered": "2018-04-12 14:48:06"
																	},
																	{
																		"action_alias": "exface.Core.UpdateData",
																		"caption": "Speichern",
																		"object_alias": "axenox.WMS.picking_order_pos",
																		"object_name": "Pickauftragsposition",
																		"triggered": "2018-04-12 16:38:22"
																	}*/
																]
														};
														
														var oTable = new sap.m.Table({
															fixedLayout: false,
															mode: sap.m.ListMode.MultiSelect,
															headerToolbar: [
																new sap.m.OverflowToolbar({
																	design: "Transparent",
																	content: [
																		new sap.m.Label({
																			text: "Wartende Online-Aktionen"
																		}),
																		new sap.m.ToolbarSpacer(),
																		new sap.m.Button({
																			text: "Abbrechen",
																			icon: "sap-icon://cancel"
																		}),
																		new sap.m.Button({
																			text: "Exportieren",
																			icon: "sap-icon://download"
																		})
																	]
																})
															],
															columns: [
																new sap.m.Column({
																	header: [
																		new sap.m.Label({
																			text: "Objekt"
																		})
																	]
																}),
																new sap.m.Column({
																	header: [
																		new sap.m.Label({
																			text: "Aktion"
																		})
																	]
																}),
																new sap.m.Column({
																	header: [
																		new sap.m.Label({
																			text: "Alias"
																		})
																	],
																	minScreenWidth: "Tablet",
																	demandPopin: true
																}),
															],
															items: {
																path: "/data",
																template: new sap.m.ColumnListItem({
																	cells: [
																		new sap.m.Text({
																			text: "{object_name}"
																		}),
																		new sap.m.Text({
																			text: "{caption}"
																		}),
																		new sap.m.Text({
																			text: "{action_alias}"
																		})
																	]
																})
															}
														}).setModel(function(){return new sap.ui.model.json.JSONModel(oData)}());
														
														_oLauncher.showDialog('Sync-Puffer', oTable, undefined, undefined, true);
													},
												}),
												new sap.m.StandardListItem({
													title: "Ausgecheckte Objekte (0)",
													type: "Active",
													press: function(){},
												}),
												new sap.m.StandardListItem({
													title: "Sync-Fehler (0)",
													type: "Active",
													press: function(){},
												}),
												new sap.m.GroupHeaderListItem({
														title: 'Offline-Data',
														upperCase: false
												}),
												new sap.m.StandardListItem({
													title: "Offline-Daten",
													info: "{/_preload/data}"
												}),
												new sap.m.StandardListItem({
													title: "Offline-Media",
													info: "{/_preload/images}"
												}),
												new sap.m.StandardListItem({
													title: "Sync Preload Data",
													icon: "sap-icon://synchronize",
													tooltip: "Offline-Daten synchronisieren",
													type: "Active",
													press: function(oEvent){
														oButton = oEvent.getSource();
														oButton.setBusyIndicatorDelay(0).setBusy(true);
														exfPreloader.syncAll().then(function(){
															oButton.setBusy(false)
														});
													},
												}),/*
												new sap.m.StandardListItem({
													title: "Storage quota",
													icon: "sap-icon://unwired",
													type: "Active",
													press: function(oEvent){
													},
												}),*/
												new sap.m.StandardListItem({
													title: "Offline-Daten zurücksetzen",
													icon: "sap-icon://sys-cancel",
													type: "Active",
													press: function(oEvent){
														oButton = oEvent.getSource();
														oButton.setBusyIndicatorDelay(0).setBusy(true);
														exfPreloader
														.reset()
														.then(() => {
															oButton.setBusy(false);
															_oLauncher.showDialog('Offline Storage', 'All preload data cleared!', 'Success');
														}).catch(() => {
															oButton.setBusy(false);
															_oLauncher.showDialog('Error!', 'Failed to clear preload data!', 'Error');
														})
													},
												})
											]
										})
									],
									beforeOpen(oEvent){
										var oPopover = oEvent.getSource();
										exfPreloader
										.getStorageInfo()
										.then(data => { 
											var info = {};
											for (var i in data) {
												oPopover.getModel().setProperty('/_preload/'+data[i].type, data[i].counter);
											}
										})
										.catch(error => {
											console.error(error);
										});
									}
								})
								.setModel(new sap.ui.model.json.JSONModel({
									_online: 0,
									_preload: {
										data: 0,
										images: 0
									}
								}));
								jQuery.sap.delayedCall(0, this, function () {
									oPopover.openBy(oButton);
								});
							}
		                }),
		                new sap.f.Avatar("exf_avatar", {
							displaySize: "XS",
							press: function(){
								window.location.href = 'login.html';
							}
		                })
					]
				})
			],
			content: [
		
			]
		})
		
		return _oShell;
	};

	this.showDialog = function (title, content, state, onCloseCallback, responsive) {
		var stretch = responsive ? jQuery.device.is.phone : false;
		var type = sap.m.DialogType.Standard;
		if (typeof content === 'string' || content instanceof String) {
			content = new sap.m.Text({
				text: content
			});
			type = sap.m.DialogType.Message;
		}
		var dialog = new sap.m.Dialog({
			title: title,
			state: state,
			type: type,
			stretch: stretch,
			content: content,
			beginButton: new sap.m.Button({
				text: 'OK',
				press: function () {
					dialog.close();
				}
			}),
			afterClose: function() {
				if (onCloseCallback) {
					onCloseCallback();
				}
				dialog.destroy();
			}
		});
	
		dialog.open();
	};

	this.showHtmlInDialog = function (title, html, state) {
		var content = new sap.ui.core.HTML({
			content: html
		});
		_oLauncher.showDialog(title, content, state);
	};
	
	this.contextBar = function(){
		var _oComponent = {};
		var _oContextBar = {
			init : function (oComponent) {
				_oComponent = oComponent;
				
				oComponent.getRouter().attachRouteMatched(function (oEvent){
					_oContextBar.load();
				});
				
				$(document).ajaxSuccess(function(event, jqXHR, ajaxOptions, data){
					var extras = {};
					if (jqXHR.responseJSON){
						extras = jqXHR.responseJSON.extras;
					} else {
						try {
							extras = $.parseJSON(jqXHR.responseText).extras;
						} catch (err) {
							extras = {};
						}
					}
					if (extras && extras.ContextBar){
						_oContextBar.refresh(extras.ContextBar);
					}
				});
			},
		
			getComponent : function() {
				return _oComponent;
			},

			load : function(delay){
				if (delay == undefined) delay = 100;
				setTimeout(function(){
					// IDEA had to disable adding context bar extras to every request due to
					// performance issues. This will be needed for asynchronous contexts like
					// user messaging, external task management, etc. So put the line back in
					// place to fetch context data with every request instead of a dedicated one.
					// if ($.active == 0 && $('#contextBar .context-bar-spinner').length > 0){
					//if ($('#contextBar .context-bar-spinner').length > 0){
						$.ajax({
							type: 'POST',
							url: 'exface/api/ui5/' + _oLauncher.getPageId() + '/context',
							dataType: 'json',
							success: function(data, textStatus, jqXHR) {
								_oContextBar.refresh(data);
							},
							error: function(jqXHR, textStatus, errorThrown){
								_oContextBar.refresh({});
							}
						});
					/*} else {
						_oContextBar.load(delay*3);
					}*/
				}, delay);
			},

			refresh : function(data){
				var oToolbar = _oShell.getHeader();
				var aItemsOld = _oShell.getHeader().getContent();
				var iItemsIndex = 5;
				var oControl = {};
				oToolbar.removeAllContent();
				
				for (var i=0; i<aItemsOld.length; i++) {
					oControl = aItemsOld[i];
					if (i < iItemsIndex || oControl.getId() == 'exf-connection-indicator' || oControl.getId() == 'exf_pagetitle' || oControl.getId() == 'exf_avatar') {
						oToolbar.addContent(oControl);
					} else {
						oControl.destroy();
					}
				}
				
				for (var id in data){
					var sColor = data[id].color ? 'background-color:'+data[id].color+' !important;' : '';
					oToolbar.insertContent(
							new sap.m.Button(id, { 
								icon: data[id].icon,
								tooltip: data[id].hint,
								text: data[id].indicator,
								press: function(oEvent) {
									var oButton = oEvent.getSource();
									_oContextBar.showMenu(oButton);
								}
							}).data('widget', data[id].bar_widget_id, true), 
							iItemsIndex);
				}
			},

			showMenu : function (oButton){
				var sPopoverId = oButton.data('widget')+"_popover";
				var iPopoverWidth = "350px";
				var iPopoverHeight = "300px";
				var oPopover = sap.ui.getCore().byId(sPopoverId);
				if (oPopover) {
					return;
				} else {
					oPopover = new sap.m.Popover(sPopoverId, {
						title: oButton.getTooltip(),
						placement: "Bottom",
						busy: true,
						contentWidth: iPopoverWidth,
						contentHeight: iPopoverHeight,
						horizontalScrolling: false,
						afterClose: function(oEvent) {
							oEvent.getSource().destroy();
						},
						content: [
							new sap.m.NavContainer({
								pages: [
									new sap.m.Page({
										showHeader: false,
										content: [
											
										]
									})
								]
							})
						]
					}).setBusyIndicatorDelay(0);
					
					jQuery.sap.delayedCall(0, this, function () {
						oPopover.openBy(oButton);
					});
				}
				
				$.ajax({
					type: 'POST',
					url: 'exface/api/ui5',
					dataType: 'script',
					data: {
						action: 'exface.Core.ShowContextPopup',
						resource: _oLauncher.getPageId(),
						element: oButton.data('widget')
					},
					success: function(data, textStatus, jqXHR) {			
						var viewMatch = data.match(/sap.ui.jsview\("(.*)"/i);
			            if (viewMatch !== null) {
			                var view = viewMatch[1];
			                //$('body').append(data);
			            } else {
			            	_oLauncher.showHtmlInDialog(text.Status, data);
			            }
			            
			            var oPopoverPage = oPopover.getContent()[0].getPages()[0];
			            oPopoverPage.removeAllContent();
			            
			            var oView = _oComponent.runAsOwner(function() {
		            		return sap.ui.view({type:sap.ui.core.mvc.ViewType.JS, viewName:view});
	            		}); 
		            	oPopoverPage.addContent(oView);
			        	oPopover.setBusy(false);
						
					},
					error: function(jqXHR, textStatus, errorThrown){
						oButton.setBusy(false);
						_oLauncher.showHtmlInDialog(textStatus, jqXHR.responseText, "error");
					}
				});
			}
		};
		return _oContextBar;
	}();

	this.getPageId = function(){
		return $("meta[name='page_id']").attr("content");
	};

	this.toggleOnlineIndicator = function() {
		sap.ui.getCore().byId('exf-connection-indicator').setIcon(navigator.onLine ? 'sap-icon://connected' : 'sap-icon://disconnected');
		_oShell.getModel().setValue("/_online", navigator.onLine);
		if (navigator.onLine) {
			_oLauncher.contextBar.load();
		}
	}
}).apply(exfLauncher);

const exfPreloader = {};
(function(){
	
	var _preloader = this;
		
	var _db = function() {
		var dexie = new Dexie('exf-preload');
		dexie.version(1).stores({
			'preloads': 'id, object',
			'preloads-info': 'type, counter'
		});
		dexie.open();
		return dexie;
	}();
	
	var _preloadTable = _db.table('preloads');
	
	var _infoTable = _db.table('preloads-info');
	
	this.addPreload = function(sAlias, aDataCols, aImageCols, sPageAlias, sWidgetId){
		_preloadTable
		.get(sAlias)
		.then(item => {
			var data = {
				id: sAlias,
				object: sAlias,
				page: sPageAlias,
				widget: sWidgetId,
				dataCols: aDataCols,
				imageCols: aImageCols
			};
			if (item === undefined) {
				_preloadTable.put(data);
			} else {
				_preloadTable.update(sAlias, data);
			}
		})
		return _preloader;
	};
	
	this.getPreload = function(sAlias, sPageAlias, sWidgetId) {
		return _preloadTable.get(sAlias);
	};
	
	this.syncAll = function(fnCallback) {
		var deferreds = [];
		_infoTable.clear();
		return _preloadTable.toArray()
		.then(data => {
			$.each(data, function(idx, item){
				deferreds.push(
			    	_preloader.sync(item.object, item.page, item.widget, item.imageCols)
			    );
			});
			// Can't pass a literal array, so use apply.
			return $.when.apply($, deferreds)
		});
	};
	
	/**
	 * @return jqXHR
	 */
	this.sync = function(sObjectAlias, sPageAlias, sWidgetId, aImageCols) {
		return $.ajax({
			type: 'POST',
			url: 'exface/api/ui5',
			dataType: 'json',
			data: {
				action: 'exface.Core.ReadPreload',
				resource: sPageAlias,
				element: sWidgetId
			}
		})
		.then(
			function(data, textStatus, jqXHR) {
				var promises = [];
				promises.push(
					_preloadTable.update(sObjectAlias, {
						response: data/*,
						lastSync: (+ new Date())*/
					})
				);
				_infoTable
				.get('data')
				.then(item => {
					item.counter = item.counter + data.data.length;
					_infoTable.put(item);
				})
				.catch(error => {
					_infoTable.put({type: 'data', counter: data.data.length});
				});
				if (aImageCols && aImageCols.length > 0) {
					for (i in aImageCols) {
						var urls = data.data.map(function(value,index) { return value[aImageCols[i]]; });
						promises.push(_preloader.syncImages(urls));
					}
				}
				return Promise.all(promises);
			},
			function(jqXHR, textStatus, errorThrown){
				exfLauncher.showHtmlInDialog(textStatus, jqXHR.responseText, "error");
				return textStatus;
			}
		);
	};
	
	this.syncImages = function (aUrls, sCacheName = 'image-cache') {
		if (window.caches === undefined) {
			console.error('Cannot preload images: Cache API not supported by browser!');
			return;
		}
		
		return window.caches
		.open(sCacheName)
		.then(cache => {
			// Remove duplicates
			aUrls = aUrls.filter((value, index, self) => { 
			    return self.indexOf(value) === index;
			});
			// Fetch and cache images
			var requests = [];
			for (var i in aUrls) {
				if (! aUrls[i]) continue;
				var request = new Request(aUrls[i]);
				requests.push(
					fetch(request.clone())
					.then(response => {
						// Check if we received a valid response
						if(! response || response.status !== 200 || response.type !== 'basic') {
						  return response;
						}
						
						// IMPORTANT: Clone the response. A response is a stream
						// and because we want the browser to consume the response
						// as well as the cache consuming the response, we need
						// to clone it so we have two streams.
						var responseToCache = response.clone();
						
						_infoTable
						.get('images')
						.then(item => {
							if (item === undefined) {
								item = {type: 'images', counter: 1};
							} else {
								item.counter = item.counter + 1;
							}
							_infoTable.put(item);
						});
						
						return cache.put(request, responseToCache);
					})
				);
			}
			return Promise.all(requests);
		});
	};
	
	this.reset = function() {
		return _db.delete();
	};
	
	this.getStorageInfo = function() {
		return _infoTable.toArray();
	};
}).apply(exfPreloader);