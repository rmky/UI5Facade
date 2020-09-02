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
	var _oAppMenu;
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
		                    	_oShell.setShowPane(! _oShell.getShowPane());
		            		}
		                }),
		                new sap.m.OverflowToolbarButton("exf-home", {
		                	text: "{i18n>WEBAPP.SHELL.HOME.TITLE}",
							icon: "sap-icon://home",
		                	press: function(oEvent){
		                		oBtn = oEvent.getSource();
		                		sap.ui.core.BusyIndicator.show(0); 
		                		window.location.href = oBtn.getModel().getProperty('/_app/home_url');
                			}
		                }),
		                new sap.m.ToolbarSpacer(),
		                new sap.m.Button("exf-pagetitle", {
		                    text: "{/_app/home_title}",
		                    //icon: "sap-icon://navigation-down-arrow",
		                    iconFirst: false,
		                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
		                    press: function(oEvent) {
		                    	oBtn = oEvent.getSource();
		                		sap.ui.core.BusyIndicator.show(0); 
		                		window.location.href = oBtn.getModel().getProperty('/_app/app_url');
		                		/*
		                		if (_oAppMenu !== undefined) {
		                			var oButton = oEvent.getSource();
			                		var eDock = sap.ui.core.Popup.Dock;
			                		_oAppMenu.open(this._bKeyboard, oButton, eDock.BeginTop, eDock.BeginBottom, oButton);
		                		}*/
		                	}
		                }),
		                new sap.m.ToolbarSpacer(),
		                new sap.m.Button("exf-network-indicator", {
		                    icon: function(){return navigator.onLine ? "sap-icon://connected" : "sap-icon://disconnected"}(),
		                    text: "0/0",
		                    layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
		                    press: function(oEvent){
								var oButton = oEvent.getSource();
								var oPopover = sap.ui.getCore().byId('exf-network-menu');
								if (oPopover === undefined) {
									oPopover = new sap.m.Popover("exf-network-menu", {
										title: "{= ${/_network/online} > 0 ? ${i18n>WEBAPP.SHELL.NETWORK.ONLINE} : ${i18n>WEBAPP.SHELL.NETWORK.OFFLINE} }",
										placement: "Bottom",
										content: [
											new sap.m.List({
												items: [
													new sap.m.GroupHeaderListItem({
														title: '{i18n>WEBAPP.SHELL.NETWORK.SYNC_MENU}',
														upperCase: false
													}),
													new sap.m.StandardListItem({
														title: "{i18n>WEBAPP.SHELL.NETWORK.SYNC_MENU_QUEUE} ({/_network/queueCnt})",
														type: "Active",
														press: async function(){
															
															/*var oData = {
																	data: [
																		{
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
																		}
																	]
															};*/
															console.log('Show offline queue');
															var oData = await exfPreloader.getActionQueueData('offline');
															
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
																			}),
																			new sap.m.Button({
																				text: "Sync",
																				icon: "sap-icon://synchronize",
																				press: function(oEvent){
																					/*if (!navigator.onLine) {
																						_oLauncher.contextBar.getComponent().showDialog('Offline Queue', 'You are offline! Offline actions can not be send to server.', 'Error');
																						return;
																					}*/
																					console.log('Offline actions sending');
																					var oButton = oEvent.getSource();
																					var table = oButton.getParent().getParent()
																					var selectedItems = table.getSelectedItems();
																					var selectedIds = [];
																					selectedItems.forEach(function(item){
																						var bindingObj = item.getBindingContext().getObject()
																						selectedIds.push(bindingObj.id);
																					})
																					oButton.setBusyIndicatorDelay(0).setBusy(true);
																					var updatePromises = [];
																					exfPreloader
																					.sendActionQueue(selectedIds)
																					.then(function(){																						
																						oButton.setBusy(false);
																						_oLauncher.contextBar.getComponent().showDialog('Offline Queue', 'All Offline Actions sent!', 'Success');
																						table.getParent().close();
																					})
																					.catch(function(){
																						oButton.setBusy(false);
																						_oLauncher.contextBar.getComponent().showDialog('Offline Queue', 'All Offline Actions sent, with errors!', 'Error');
																						table.getParent().close();
																					})
																				},
																			})																			
																		]
																	})
																],
																columns: [
																	new sap.m.Column({
																		visible: false
																	}),
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
																				//text: "Alias"
																				text: 'Triggered'
																			})
																		]
																	}),
																	new sap.m.Column({
																		header: [
																			new sap.m.Label({
																				//text: "Alias"
																				text: 'Status'
																			})
																		]
																	}),
																],
																items: {
																	path: "/data",
																	template: new sap.m.ColumnListItem({
																		cells: [
																			new sap.m.Text({
																				//text: "{object_name}"
																				text: "{id}"
																			}),
																			new sap.m.Text({
																				//text: "{object_name}"
																				text: "{object}"
																			}),
																			new sap.m.Text({
																				//text: "{caption}"
																				text: "{action_alias}"
																			}),
																			new sap.m.Text({
																				//text: "{action_alias}"
																				text: "{triggered}"
																			}),
																			new sap.m.Text({
																				//text: "{action_alias}"
																				text: "{status}"
																			})
																		]
																	})
																}
															}).setModel(function(){return new sap.ui.model.json.JSONModel(oData)}());
															
															_oLauncher.contextBar.getComponent().showDialog('Offline action queue', oTable, undefined, undefined, true);
														},
													}),
													new sap.m.StandardListItem({
														title: "{i18n>WEBAPP.SHELL.NETWORK.SYNC_MENU_ERRORS} ({/_network/syncErrorCnt})",
														type: "Active",
														type: "Active",
														press: async function(){
															console.log('Show errors sent actions');
															var oData = await exfPreloader.getActionQueueData('error');
															
															var oTable = new sap.m.Table({
																fixedLayout: false,
																mode: sap.m.ListMode.MultiSelect,
																headerToolbar: [
																	new sap.m.OverflowToolbar({
																		design: "Transparent",
																		content: [
																			new sap.m.Label({
																				text: "Fehlerhafte Offline-Aktionen"
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
																				//text: "Alias"
																				text: 'Triggered'
																			})
																		]
																	}),
																	new sap.m.Column({
																		header: [
																			new sap.m.Label({
																				//text: "Alias"
																				text: 'Status'
																			})
																		]
																	}),
																	new sap.m.Column({
																		header: [
																			new sap.m.Label({
																				//text: "Alias"
																				text: 'Response'
																			})
																		]
																	})
																],
																items: {
																	path: "/data",
																	template: new sap.m.ColumnListItem({
																		cells: [
																			new sap.m.Text({
																				//text: "{object_name}"
																				text: "{object}"
																			}),
																			new sap.m.Text({
																				//text: "{caption}"
																				text: "{action_alias}"
																			}),
																			new sap.m.Text({
																				//text: "{action_alias}"
																				text: "{triggered}"
																			}),
																			new sap.m.Text({
																				//text: "{action_alias}"
																				text: "{status}"
																			}),
																			new sap.m.Text({
																				//text: "{action_alias}"
																				text: "{response}"
																			})
																		]
																	})
																}
															}).setModel(function(){return new sap.ui.model.json.JSONModel(oData)}());
															
															_oLauncher.contextBar.getComponent().showDialog('Errors', oTable, undefined, undefined, true);
														}
													}),
													new sap.m.GroupHeaderListItem({
														title: '{i18n>WEBAPP.SHELL.PRELOAD.MENU}',
														upperCase: false
													}),
													new sap.m.StandardListItem({
														title: "{i18n>WEBAPP.SHELL.PRELOAD.MENU_SYNC}",
														tooltip: "{i18n>WEBAPP.SHELL.PRELOAD.MENU_SYNC_TOOLTIP}",
														icon: "sap-icon://synchronize",
														type: "Active",
														press: function(oEvent){
															oButton = oEvent.getSource();
															oButton.setBusyIndicatorDelay(0).setBusy(true);
															exfPreloader.syncAll().then(function(){
																oButton.setBusy(false)
															});
														},
													}),
													new sap.m.StandardListItem({
														title: "Storage quota",
														icon: "sap-icon://unwired",
														type: "Active",
														press: function(oEvent){
															exfPreloader.showStorage();
														},
													}),
													new sap.m.StandardListItem({
														title: "{i18n>WEBAPP.SHELL.PRELOAD.MENU_RESET}",
														tooltip: "{i18n>WEBAPP.SHELL.PRELOAD.MENU_RESET_TOOLTIP}",
														icon: "sap-icon://sys-cancel",
														type: "Active",
														press: function(oEvent){
															oButton = oEvent.getSource();
															oButton.setBusyIndicatorDelay(0).setBusy(true);
															exfPreloader
															.reset()
															.then(() => {
																oButton.setBusy(false);
																_oLauncher.contextBar.getComponent().showDialog('Offline Storage', 'All preload data cleared!', 'Success');
															}).catch(() => {
																oButton.setBusy(false);
																_oLauncher.contextBar.getComponent().showDialog('Error!', 'Failed to clear preload data!', 'Error');
															})
														},
													})
												]
											})
										]
									})
									.setModel(oButton.getModel())
									.setModel(oButton.getModel('i18n'), 'i18n');
								}
								
								jQuery.sap.delayedCall(0, this, function () {
									oPopover.openBy(oButton);
								});
							}
		                }),		                
					]
				})
			],
			content: [
		
			]
		})
		.setModel(new sap.ui.model.json.JSONModel({
			_network: {
				online: navigator.onLine,
				queueCnt: 0,
				syncErrorCnt: 0
			}
		}));
		
		return _oShell;
	};
	
	this.setAppMenu = function (oControl) {
		_oAppMenu = oControl;
	};
	
	this.contextBar = function(){
		var _oComponent = {};
		var _oContextBar = {
			init : function (oComponent) {
				_oComponent = oComponent;
				
				// Give the shell the translation model of the component
				_oShell.setModel(oComponent.getModel('i18n'), 'i18n');
				
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
							url: 'api/ui5/' + _oLauncher.getPageId() + '/context',
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
					if (i < iItemsIndex || oControl.getId() == 'exf-network-indicator' || oControl.getId() == 'exf-pagetitle' || oControl.getId() == 'exf-user-icon') {
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
					oPopover.addStyleClass('exf-context-popup');
					
					jQuery.sap.delayedCall(0, this, function () {
						oPopover.openBy(oButton);
					});
				}
				
				$.ajax({
					type: 'POST',
					url: 'api/ui5',
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
			            	_oComponent.showAjaxErrorDialog(jqXHR);
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
						_oComponent.showAjaxErrorDialog(jqXHR);
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
		sap.ui.getCore().byId('exf-network-indicator').setIcon(navigator.onLine ? 'sap-icon://connected' : 'sap-icon://disconnected');
		_oShell.getModel().setProperty("/_network/online", navigator.onLine);
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
			'actionQueue': 'id, object, action'
		});
		dexie.open();
		return dexie;
	}();
	
	var _preloadTable = _db.table('preloads');
	var _actionsTable = _db.table('actionQueue');
	
	this.addPreload = function(sAlias, aDataCols, aImageCols, sPageAlias, sWidgetId){		
		_preloadTable
		.get(sAlias)
		.then(item => {
			var data = {
				id: sAlias,
				object: sAlias
			};
			
			if (aDataCols) { data.dataCols = aDataCols; }
			if (aImageCols) { data.imageCols = aImageCols; }
			if (sPageAlias) { data.page = sPageAlias; }
			if (sWidgetId) { data.widget = sWidgetId; }
			
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
		return _preloadTable.toArray()
		.then(data => {
			$.each(data, function(idx, item){
				deferreds.push(
			    	_preloader
			    	.sync(item.object, item.page, item.widget, item.imageCols)
			    );
			});
			// Can't pass a literal array, so use apply.
			return $.when.apply($, deferreds)
		})
		.catch(error => {
			exfLauncher.contextBar.getComponent().showErrorDialog('See console for details.', 'Preload sync failed!');
		});
	};
	
	/**
	 * @return jqXHR
	 */
	this.sync = function(sObjectAlias, sPageAlias, sWidgetId, aImageCols) {
		console.log('Syncing preload for object "' + sObjectAlias + '", widget "' + sWidgetId + '" on page "' + sPageAlias + '"');
		if (! sPageAlias || ! sWidgetId) {
			throw {"message": "Cannot sync preload for object " + sObjectAlias + ": incomplete preload configuration!"};
		}
		return $.ajax({
			type: 'POST',
			url: 'api/ui5',
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
						response: data,
						lastSync: (+ new Date())
					})
				);
				if (aImageCols && aImageCols.length > 0) {
					for (i in aImageCols) {
						var urls = data.rows.map(function(value,index) { return value[aImageCols[i]]; });
						promises.push(_preloader.syncImages(urls));
					}
				}
				return Promise.all(promises);
			},
			function(jqXHR, textStatus, errorThrown){
				exfLauncher.contextBar.getComponent().showAjaxErrorDialog(jqXHR);
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
						
						return cache.put(request, responseToCache);
					})
				);
			}
			return Promise.all(requests);
		});
	};
	
	this.reset = function() {
		var clear = _preloadTable.toArray()
		.then(function(dbContent) {
			var promises = [];
			dbContent.forEach(function(element) {
				promises.push(
					_preloadTable.update(element.id, {
						response: {},
						lastSync: 'not synced'
					})
				);
			});
			return Promise.all(promises);
		});
		return clear;
	};
	
	this.showStorage = async function() {
		console.log('Storage clicked');
		//check if service worker supported
		/*if (!('serviceWorker' in navigator)) {
			exfLauncher.contextBar.getComponent().showErrorDialog('Service worker not available.', 'Storage quota failed!');
			return;
		}*/		
		var dialog = new sap.m.Dialog({title: "Storage quota", icon: "sap-icon://unwired"});
		var button = new sap.m.Button({
			icon: 'sap-icon://font-awesome/close',
            text: "Close",
            press: function() {dialog.close();},
        });
		dialog.addButton(button);
		list = new sap.m.List({});
		//check if possible to acces storage (means https connection)
		if (navigator.storage) {
			var promise = navigator.storage.estimate()
			.then(function(estimate) {				
				list = new sap.m.List({
					items: [
						new sap.m.GroupHeaderListItem({
							title: 'Overview',
							upperCase: false
						}),
						new sap.m.DisplayListItem({
							label: "Total Space",
							value: Number.parseFloat(estimate.quota/1024/1024).toFixed(2) + ' MB'
						}),
						new sap.m.DisplayListItem({
							label: "Used Space",
							value: Number.parseFloat(estimate.usage/1024/1024).toFixed(2) + ' MB'
						}),
						new sap.m.DisplayListItem({
							label: "Percentage Used",
							value: Number.parseFloat(100/estimate.quota*estimate.usage).toFixed(2) + ' %'
						})
					]
				});
				if (estimate.usageDetails) {
					list.addItem(new sap.m.GroupHeaderListItem({
							title: 'Details',
							upperCase: false
					}));
					Object.keys(estimate.usageDetails).forEach(function(key) {
						list.addItem(new sap.m.DisplayListItem({
								label: key,
								value: Number.parseFloat(estimate.usageDetails[key]/1024/1024).toFixed(2) + ' MB'
							})
						);
					});
				}				
			})
			.catch(function(error) {
				console.error(error);
				list.addItem(new sap.m.GroupHeaderListItem({
					title: 'Storage quota failed! See console for details.',
					upperCase: false
				}))
			});
			//wait for the promise to resolve
			await promise;
		} else {
			list.addItem(new sap.m.GroupHeaderListItem({
				title: 'Overview showing used storage space not possible!',
				upperCase: false
			}))
		}
		promise = _preloadTable.toArray()
		.then(function(dbContent){
			list.addItem(new sap.m.GroupHeaderListItem({
				title: 'Synced content',
				upperCase: false
			}));
			console.log('Content', dbContent);
			var oTable = new sap.m.Table({
				fixedLayout: false,
				columns: [
		            new sap.m.Column({
		                header: new sap.m.Label({
		                    text: 'ID'
		                })
		            }),
		            new sap.m.Column({
		                header: new sap.m.Label({
		                    text: 'Object'
		                })
		            }),
		            new sap.m.Column({
		                header: new sap.m.Label({
		                    text: 'WidgetID'
		                })
		            }),
		            new sap.m.Column({
		                header: new sap.m.Label({
		                    text: 'Datasets'
		                })
		            }),
		            ,
		            new sap.m.Column({
		                header: new sap.m.Label({
		                    text: 'Last synced'
		                })
		            })
		        ]
			});
			dbContent.forEach(function(element) {
				oRow = new sap.m.ColumnListItem();
				oRow.addCell(new sap.m.Text({text: element.id}));
				oRow.addCell(new sap.m.Text({text: element.object}));
				oRow.addCell(new sap.m.Text({text: element.widget}));
				if (element.response && element.response.rows) {
					oRow.addCell(new sap.m.Text({text: element.response.rows.length}));
					oRow.addCell(new sap.m.Text({text: new Date(element.lastSync).toLocaleString()}));
				} else {
					oRow.addCell(new sap.m.Text({text: '0'}));

					oRow.addCell(new sap.m.Text({text: 'not synced'}));
				}
				oTable.addItem(oRow);						
			});
			dialog.addContent(list);
			dialog.addContent(oTable);	
		})
		.catch(function(error) {
			console.error(error);
			list.addItem(new sap.m.GroupHeaderListItem({
				title: 'Overview showing db content not possbile! See console for details.',
				upperCase: false
			}))
			dialog.addContent(list);				
		})
		//wait for the promise to resolve
		await promise;
		dialog.open();
		return;
	};
	
	this.addAction = async function(offlineAction, objectAlias) {
		var success = false;
		var date = (+ new Date());
		var data = {
			id: date,
			object: objectAlias,
			action: offlineAction.data.action,
			request: offlineAction,
			triggered: new Date(date).toLocaleString(),
			status: 'offline'
		};
		if (offlineAction.headers) {
			data.headers = offlineAction.headers
		}
		return _actionsTable.put(data)
	};
	
	this.getActionQueueData = function(filter) {
		return _actionsTable.toArray()
		.then(function(dbContent) {
			var oData = {}
			var data = [];
			dbContent.forEach(function(element) {
				if (element.status != filter) {
					return;
				}
				item = {
						id: element.id,
						action_alias: element.action,
						object: element.object,
						triggered: element.triggered,
						status: element.status
				}
				if (element.response) {
					item.response = element.response;
				}
				data.push(item);
				return;
			})
			oData.data = data;
			return oData;
		})
		.catch(function(error) {
			return {data: []};
		})
	};
	
	this.sendActionQueue = function(selectedIds) {
		var ajaxPromises = [];
		selectedIds.forEach(async function(id){
			var promise = _actionsTable.get(id)
			.then(function(element){
				var ajaxObject = element.request;
				ajaxObject.success = function(data, textStatus, jqXHR) {
					console.log('Success sending');
					var update = _actionsTable.update(element.id, {
						status: 'success',
						response: data
					})
					.then(function (updated){
						if (updated) {
							console.log ("Action was updated");
						} else {
							console.log ("Nothing was updated - there was no action with id: ", element.id);
						}
					});
				}
				ajaxObject.error = function(jqXHR, textStatus, errorThrown) {
					console.log('Error Server response');
					var update = _actionsTable.update(element.id, {
						status: 'error',
						response: jqXHR.responseText
					})
					.then(function (updated){
						if (updated) {
							console.log ("Action was updated");
						} else {
							console.log ("Nothing was updated - there were no action with id: ", element.id);
						}
					});
				}
				ajaxPromises.push($.ajax(ajaxObject));
			})
		})
		
		return Promise.all(ajaxPromises);
	};
}).apply(exfPreloader);