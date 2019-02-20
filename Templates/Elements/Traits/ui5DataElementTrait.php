<?php
namespace exface\OpenUI5Template\Templates\Elements\Traits;

use exface\Core\Widgets\Data;
use exface\Core\Widgets\DataTable;
use exface\OpenUI5Template\Templates\Interfaces\ui5ControllerInterface;
use exface\OpenUI5Template\Templates\Elements\ui5AbstractElement;
use exface\OpenUI5Template\Templates\Elements\ui5DataConfigurator;
use exface\Core\Interfaces\Widgets\iShowImage;

/**
 * This trait helps wrap thrid-party data widgets (like charts, image galleries, etc.) in 
 * UI5 panels with standard toolbars, a configurator dialog, etc. 
 * 
 * @author Andrej Kabachnik
 *
 */
trait ui5DataElementTrait {
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        $configuratorElement = $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget());
        $configuratorElement->setModelNameForConfig($this->getModelNameForConfigurator());
        if ($this->isWrappedInDynamicPage()) {
            $configuratorElement->setIncludeFilterTab(false);
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $widget = $this->getDataWidget();
        $controller = $this->getController();
        
        $controller->addMethod('onUpdateFilterSummary', $this, '', $this->buildJsFilterSummaryUpdater());
        $controller->addMethod('onLoadData', $this, 'oControlEvent, keep_page_pos', $this->buildJsDataLoader());
        $this->initConfiguratorControl($controller);
        
        // Reload the data every time the view is shown. This is important, because otherwise 
        // old rows may still be visible if a dialog is open, closed and then reopened for another 
        // instance.
        // The data resetter will empty the table as soon as the view is opened, then the refresher
        // is run after all the view loading logic finished - that's what the setTimeout() is for -
        // otherwise the refresh would run before the view finished initializing, before the prefill
        // is started and will probably be empty.
        $controller->addOnShowViewScript("{$this->buildJsDataResetter()}; setTimeout(function(){ {$this->buildJsRefresh()} }, 0);");
        
        if ($widget->isPreloadDataEnabled()) {
            $dataCols = [];
            $imgCols = [];
            foreach ($widget->getColumns() as $col) {
                $dataCols[] = $col->getDataColumnName();
                if ($col->getCellWidget() instanceof iShowImage) {
                    $imgCols[] = $col->getDataColumnName();
                }
            }
            $preloadDataCols = json_encode($dataCols);
            $preloadImgCols = json_encode($imgCols);
            $controller->addOnDefineScript("exfPreloader.addPreload('{$this->getMetaObject()->getAliasWithNamespace()}', {$preloadDataCols}, {$preloadImgCols}, '{$widget->getPage()->getId()}', '{$widget->getId()}');");
        }
        
        $js = $this->buildJsConstructorForControl();
        
        $initConfigModel = ".setModel(new sap.ui.model.json.JSONModel(), '{$this->getModelNameForConfigurator()}')";
        
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js) . $initConfigModel;
        } else {
            return $js . $initConfigModel;
        }
    }
    
    /**
     * Returns the constructor for the inner data control (e.g. table, chart, etc.)
     * 
     * @param string $oControllerJs
     * @return string
     */
    abstract protected function buildJsConstructorForControl($oControllerJs = 'oController') : string;
    
    /**
     * Wraps the given content in a sap.m.Panel with data-specific toolbars (configurator button, etc.).
     * 
     * This is usefull for third-party widget libraries, that need this wrapper to look like UI5 controls.
     * 
     * @param string $contentConstructorsJs
     * @param string $oControllerJs
     * @param string $caption
     * 
     * @return string
     */
    protected function buildJsPanelWrapper(string $contentConstructorsJs, string $oControllerJs = 'oController', string $toolbar = null)  : string
    {
        $toolbar = $toolbar ?? $this->buildJsToolbar($oControllerJs);
        return <<<JS
        new sap.m.Panel({
            height: "100%",
            headerToolbar: [
                {$toolbar}.addStyleClass("sapMTBHeader-CTX")
            ],
            content: [
                {$contentConstructorsJs}
            ]
        })
        
JS;
    }
    
    /**
     * Returns the constructor for the table's main toolbar (OverflowToolbar).
     *
     * The toolbar contains the caption, all the action buttons, the quick search
     * and the button for the personalization dialog as well as the P13nDialog itself.
     *
     * The P13nDialog is appended to the toolbar wrapped in an invisible container in
     * order not to affect the overflow behavior. The dialog must be included in the
     * toolbar to ensure it is destroyed with the toolbar and does not become an
     * orphan (e.g. when the view containing the table is destroyed).
     * 
     * @param string $oControllerJsVar
     * @param string $leftExtras
     * @param string $rightExtras
     *
     * @return string
     */
    protected function buildJsToolbar($oControllerJsVar = 'oController', string $leftExtras = null, string $rightExtras = null)
    {
        return <<<JS

			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$this->buildJsToolbarContent($oControllerJsVar, $leftExtras, $rightExtras)}
				]
			})

JS;
    }
    
    /**
     * 
     * @param string $oControllerJsVar
     * @param string $leftExtras
     * @param string $rightExtras
     * @return string
     */
    protected function buildJsToolbarContent($oControllerJsVar = 'oController', string $leftExtras = null, string $rightExtras = null) : string
    {
        $heading = $this->isWrappedInDynamicPage() ? '' : 'new sap.m.Label({text: "' . $this->getCaption() . '"}),';
        
        $leftExtras = $leftExtras === null ? '' : rtrim($leftExtras, ", ") . ',';
        $rightExtras = $rightExtras === null ? '' : rtrim($leftExtras, ", ") . ',';
        
        return <<<JS

                    {$heading}
                    {$leftExtras}
			        new sap.m.ToolbarSpacer(),
                    {$this->buildJsButtonsConstructors()}
                    {$rightExtras}
                    {$this->buildJsQuickSearchConstructor()}
					{$this->buildJsConfiguratorButtonConstructor()}

JS;
    }
    
    /**
     * Returns the text to be shown a table title
     *
     * @return string
     */
    public function getCaption() : string
    {
        $widget = $this->getWidget();
        return $widget->getCaption() ? $widget->getCaption() : $widget->getMetaObject()->getName();
    }

    /**
     * 
     * @return bool
     */
    protected function hasActionButtons() : bool
    {
        return $this->getWidget()->hasButtons();
    }
    
    /**
     * Returns a comma separated list of javascript constructors for all buttons of the table.
     *
     * Must end with a comma unless it is an empty string!
     * 
     * @return string
     */
    protected function buildJsButtonsConstructors()
    {
        if ($this->hasActionButtons() === false) {
            return '';
        }
        
        $widget = $this->getWidget();
        $buttons = '';
        foreach ($widget->getToolbars() as $toolbar) {
            if ($toolbar->getIncludeSearchActions()){
                $search_button_group = $toolbar->getButtonGroupForSearchActions();
            } else {
                $search_button_group = null;
            }
            foreach ($widget->getToolbarMain()->getButtonGroups() as $btn_group) {
                if ($btn_group === $search_button_group){
                    continue;
                }
                $buttons .= ($buttons && $btn_group->getVisibility() > EXF_WIDGET_VISIBILITY_OPTIONAL ? ",\n new sap.m.ToolbarSeparator()" : '');
                foreach ($btn_group->getButtons() as $btn) {
                    $buttons .= $this->getTemplate()->getElement($btn)->buildJsConstructor() . ",\n";
                }
            }
        }
        return $buttons;
    }
    
    /**
     * Returns the JS constructor for the configurator button.
     * 
     * Must end with a comma unless it is an empty string!
     * 
     * @param string $oControllerJs
     * @return string
     */
    protected function buildJsConfiguratorButtonConstructor(string $oControllerJs = 'oController', string $buttonType = 'Default') : string
    {
        return <<<JS
        
                    new sap.m.OverflowToolbarButton({
                        type: sap.m.ButtonType.{$buttonType},
                        icon: "sap-icon://drop-down-list",
                        text: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        tooltip: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),
                        press: function() {
                			{$this->getController()->buildJsDependentControlSelector('oConfigurator', $this, $oControllerJs)}.open();
                		}
                    }),
                    
JS;
    }
    
    /**
     * Initializes the configurator control (sap.m.P13nDialog or similar) and makes it available in the given controller.
     * 
     * Use buildJsConfiguratorOpen() to show the configurator dialog. 
     * 
     * @param ui5ControllerInterface $controller
     * 
     * @return ui5AbstractElement
     */
    protected function initConfiguratorControl(ui5ControllerInterface $controller) : ui5AbstractElement
    {
        $controller->addDependentControl('oConfigurator', $this, $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget()));
        return $this;
    }
    
    /**
     *
     * @return bool
     */
    protected function hasQuickSearch() : bool
    {
        return $this->getWidget() instanceof DataTable;
    }
    
    /**
     * Returns a JS snippet, that performs the given $onFailJs if required filters are missing.
     * 
     * @param string $onFailJs
     * @return string
     */
    protected function buildJsCheckRequiredFilters(string $onFailJs) : string
    {
        $configurator_element = $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget());
        return <<<JS

                try {
                    if (! {$configurator_element->buildJsValidator()}) {
                        {$onFailJs};
                    }
                } catch (e) {
                    console.warn('Could not check filter validity - ', e);
                }      
                
JS;
    }
                        
    abstract protected function buildJsDataResetter() : string;
    
    /**
     * Returns the definition of a javascript function to fill the table with data: onLoadDataTableId(oControlEvent).
     *
     * @return string
     */
    protected function buildJsDataLoader($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos')
    {
        // Before we load anything, we need to make sure, the view data is loaded.
        // The view model has a special property to indicate if view (prefill) data
        // is being loaded. So we check that property and, if it shows a prefill
        // running right now, we listen for changes on the property. Once it is not
        // set to true anymore, we can do the refresh. The setTimeout() wrapper is
        // needed to make sure all filters bound to the prefill model got their values!
        $js = <<<JS
        
                var oViewModel = sap.ui.getCore().byId("{$this->getId()}").getModel("view");
                var sPendingPropery = "/_prefill/pending";
                if (oViewModel.getProperty(sPendingPropery) === true) {
                    var oPrefillBinding = new sap.ui.model.Binding(oViewModel, sPendingPropery, oViewModel.getContext(sPendingPropery));
                    var fnPrefillHandler = function(oEvent) {
                        oPrefillBinding.detachChange(fnPrefillHandler);
                        setTimeout(function() {
                            {$this->buildJsRefresh()};
                        }, 0);
                    };
                    oPrefillBinding.attachChange(fnPrefillHandler);
                    return;
                }

                {$this->buildJsDataLoaderPrepare()}

JS;
                
                if (! $this->isLazyLoading()) {
                    $js .= $this->buildJsDataLoaderFromLocal($oControlEventJsVar, $keepPagePosJsVar);
                } else {
                    $js .= $this->buildJsDataLoaderFromServer($oControlEventJsVar, $keepPagePosJsVar);
                }
                
                return $js;
    }
    
    
    
    /**
     *
     * @return string
     */
    protected function buildJsDataLoaderFromServer($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos')
    {
        $widget = $this->getWidget();
        
        if ($widget->isPreloadDataEnabled()) {
            $doLoad = $this->buildJsDataLoaderFromServerPreload('oModel', 'params');
        } else {
            $doLoad = $this->buildJsDataLoaderFromServerRemote('oModel', 'params');
        }
        
        if ($this->hasQuickSearch()) {
            $quickSearchParam = "params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();";
        }
        
        return <<<JS
        
        		var oTable = sap.ui.getCore().byId("{$this->getId()}");
                var params = {
					action: "{$widget->getLazyLoadingActionAlias()}",
					resource: "{$this->getPageId()}",
					element: "{$widget->getId()}",
					object: "{$widget->getMetaObject()->getId()}"
				};
        		var oModel = oTable.getModel();
                var oData = oModel.getData();
                var oController = this;
                
                {$this->buildJsCheckRequiredFilters("oModel.setData({}); return;")}
                
                {$this->buildJsBusyIconShow()}
                
        		// Add quick search
                {$quickSearchParam}
                
                // Add configurator data
                params.data = {$this->getP13nElement()->buildJsDataGetter()};
                
                {$this->buildJsDataLoaderParams($oControlEventJsVar, 'params', $keepPagePosJsVar)}
                
                // Add sorters and filters from P13nDialog
                var aSortItems = sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}').getSortItems();
                for (var i in aSortItems) {
                    params.sort = (params.sort ? params.sort+',' : '') + aSortItems[i].getColumnKey();
                    params.order = (params.order ? params.order+',' : '') + (aSortItems[i].getOperation() == 'Ascending' ? 'asc' : 'desc');
                }
                
                {$doLoad}
                
JS;
    }
                
    protected function buildJsDataLoaderParams(string $oControlEventJsVar = 'oControlEvent', string $oParamsJs = 'params') : string
    {
        return '';
    }
                
    protected function buildJsDataLoaderFromServerPreload(string $oModelJs = 'oModel', string $oParamsJs = 'params', string $growingJsVar = 'growing') : string
    {
        $widget = $this->getWidget();
        return <<<JS
        
                exfPreloader
                .getPreload('{$widget->getMetaObject()->getAliasWithNamespace()}')
                .then(preload => {
                    if (preload !== undefined && preload.response !== undefined && preload.response.data !== undefined) {
                        var aData = preload.response.data;
                        if ({$oParamsJs}.data && {$oParamsJs}.data.filters && {$oParamsJs}.data.filters.conditions) {
                            var conditions = {$oParamsJs}.data.filters.conditions;
                            var fnFilter;
                            
                            for (var i in conditions) {
                                var cond = conditions[i];
                                if (cond.value === undefined || cond.value === null || cond.value === '') continue;
                                switch (cond.comparator) {
                                    case '==':
                                        aData = aData.filter(oRow => {
                                            return oRow[cond.expression] == cond.value
                                        });
                                        break;
                                    case '!==':
                                        aData = aData.filter(oRow => {
                                            return oRow[cond.expression] !== cond.value
                                        });
                                        break;
                                    case '!=':
                                        var val = cond.value.toString().toLowerCase();
                                        aData = aData.filter(oRow => {
                                            if (oRow[cond.expression] === undefined) return true;
                                            return ! oRow[cond.expression].toString().toLowerCase().includes(val);
                                        });
                                        break;
                                    case '=':
                                    default:
                                        var val = cond.value.toString().toLowerCase();
                                        aData = aData.filter(oRow => {
                                            if (oRow[cond.expression] === undefined) return false;
                                            return oRow[cond.expression].toString().toLowerCase().includes(val);
                                        });
                                }
                            }
                            
                            if ({$oParamsJs}.q !== undefined && {$oParamsJs}.q !== '') {
                                var sQuery = {$oParamsJs}.q.toString().toLowerCase();
                                aData = aData.filter(oRow => {
                                    if (oRow[cond.expression] === undefined) return false;
                                    return {$this->buildJsQuickSearch('sQuery', 'oRow')};
                                });
                            }
                            
                            var iFiltered = aData.length;
                        }
                        
                        if ({$oParamsJs}.start >= 0 && {$oParamsJs}.length > 0) {
                            aData = aData.slice({$oParamsJs}.start, {$oParamsJs}.start+{$oParamsJs}.length);
                        }
                        
                        oModel.setData($.extend({}, preload.response, {data: aData, recordsFiltered: iFiltered}));
                        {$this->buildJsDataLoaderOnLoaded($oModelJs, $growingJsVar)}
                        {$this->buildJsBusyIconHide()}
                    } else {
                        {$this->buildJsDataLoaderFromServerRemote($oModelJs, 'params', $growingJsVar)}
                    }
                });
                
JS;
    }
    
    protected function buildJsDataLoaderFromServerRemote(string $oModelJs = 'oModel', string $oParamsJs = 'params') : string
    {
        return <<<JS
        
                var fnCompleted = function(oEvent){
                    {$this->buildJsBusyIconHide()}
        			if (oEvent.getParameters().success) {
                        {$this->buildJsDataLoaderOnLoaded('this')}
                    } else {
                        var error = oEvent.getParameters().errorobject;
                        if (navigator.onLine === false) {
                            if (oData.length = 0) {
                                {$this->buildJsOfflineHint('oTable')}
                            } else {
                                {$this->getController()->buildJsComponentGetter()}.showDialog('{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR')}', '{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR_TITLE')}', 'Error');
                            }
                        } else {
                            {$this->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
                        }
                    }
                    
                    this.detachRequestCompleted(fnCompleted);
        		};
        		
        		oModel.attachRequestCompleted(fnCompleted);
        		
                oModel.loadData("{$this->getAjaxUrl()}", {$oParamsJs});
                
JS;
    }
    
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        if ($this->isWrappedInDynamicPage()) {
            $dynamicPageFixes = <<<JS
            
                        if (sap.ui.Device.system.phone) {
                            sap.ui.getCore().byId('{$this->getIdOfDynamicPage()}').setHeaderExpanded(false);
                        }
                        // Redraw the table to make it fit the page height agian. Otherwise it would be
                        // of default height after dialogs close, etc.
                        sap.ui.getCore().byId('{$this->getId()}').invalidate();
JS;
        }
        
        return <<<JS
        
            oTable.getModel("{$this->getModelNameForConfigurator()}").setProperty('/filterDescription', {$this->getController()->buildJsMethodCallFromController('onUpdateFilterSummary', $this, '', 'oController')});
            
            {$dynamicPageFixes}
			
JS;
    }
            
    protected function getModelNameForConfigurator() : string
    {
        return 'configurator';
    }
    
    /**
     *
     * @return ui5DataConfigurator
     */
    protected function getP13nElement()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget());
    }
    
    protected function getIdOfDynamicPage() : string
    {
        return $this->getId() . "_DynamicPageWrapper";
    }
    
    protected function buildJsDataLoaderPrepare() : string
    {
        return '';
    }
    
    protected function buildJsOfflineHint(string $oTableJs = 'oTable') : string
    {
        return '';
    }
    
    /**
     * Returns TRUE if the table will be wrapped in a sap.f.DynamicPage to create a Fiori ListReport
     *
     * @return boolean
     */
    protected function isWrappedInDynamicPage()
    {
        return $this->getWidget()->hasParent() || $this->getWidget()->getHideHeader() ? false : true;
    }
    
    /**
     * Returns TRUE if this table uses a remote data source and FALSE otherwise.
     *
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(true);
    }
    
    /**
     * Wraps the given content in a constructor for the sap.f.DynamicPage used to create the Fiori list report floorplan.
     *
     * @param string $content
     * @return string
     */
    protected function buildJsPage(string $content) : string
    {
        foreach ($this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions()->getButtons() as $btn) {
            if ($btn->getAction()->isExactly('exface.Core.RefreshWidget')){
                $btn->setShowIcon(false);
                $btn->setHint($btn->getCaption());
                $btn->setCaption($this->translate('WIDGET.DATATABLE.GO_BUTTON_TEXT'));
            }
            $top_buttons .= $this->getTemplate()->getElement($btn)->buildJsConstructor() . ',';
        }
        
        if ($this->getView()->isWebAppRoot() === true) {
            $title = <<<JS
            
                            new sap.m.Title({
                                text: "{$this->getCaption()}"
                            })
                            
JS;
        } else {
            $title = <<<JS
            
                            new sap.m.HBox({
                                items: [
                                    new sap.m.Button({
                                        icon: "sap-icon://nav-back",
                                        press: [oController.onNavBack, oController],
                                        type: sap.m.ButtonType.Transparent
                                    }).addStyleClass('exf-page-heading-btn'),
                                    new sap.m.Title({
                                        text: "{$this->getCaption()}"
                                    })
                                ]
                            })
                            
JS;
        }
        
        return <<<JS
        
        new sap.f.DynamicPage("{$this->getIdOfDynamicPage()}", {
            fitContent: true,
            preserveHeaderStateOnScroll: true,
            headerExpanded: true,
            title: new sap.f.DynamicPageTitle({
				expandedHeading: [
                    {$title}
				],
                snappedHeading: [
                    new sap.m.VBox({
                        items: [
        					{$title},
                            new sap.m.Text({
                                text: "{{$this->getModelNameForConfigurator()}>/filterDescription}"
                            })
                        ]
                    })
				],
				actions: [
				    {$top_buttons}
				]
            }),
            
			header: new sap.f.DynamicPageHeader({
                pinnable: true,
				content: [
                    new sap.ui.layout.Grid({
                        defaultSpan: "XL2 L3 M4 S12",
                        content: [
							{$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsFilters()}
						]
                    })
				]
			}),
			
            content: [
                {$content}
            ]
        })
JS;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsFilterSummaryUpdater()
    {
        $filter_checks = '';
        foreach ($this->getDataWidget()->getFilters() as $fltr) {
            $elem = $this->getTemplate()->getElement($fltr);
            $filter_checks .= 'if(' . $elem->buildJsValueGetter() . ") {filtersCount++; filtersList += (filtersList == '' ? '' : ', ') + '{$elem->getCaption()}';} \n";
        }
        return <<<JS
                var filtersCount = 0;
                var filtersList = '';
                {$filter_checks}
                if (filtersCount > 0) {
                    return '{$this->translate('WIDGET.DATATABLE.FILTERED_BY')} (' + filtersCount + '): ' + filtersList;
                } else {
                    return '{$this->translate('WIDGET.DATATABLE.FILTERED_BY')}: {$this->translate('WIDGET.DATATABLE.FILTERED_BY_NONE')}';
                }
JS;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsFilterSummaryFunctionName() {
        return "{$this->buildJsFunctionPrefix()}CountFilters";
    }
    
    /**
     * Returns the constructor for the sap.m.SearchField for toolbar quick search.
     *
     * Must end with a comma unless it is an empty string!
     *
     * @return string
     */
    protected function buildJsQuickSearchConstructor() : string
    {
        if ($this->hasQuickSearch() === false) {
            return '';
        }
        
        return <<<JS
        
                    new sap.m.SearchField("{$this->getId()}_quickSearch", {
                        width: "200px",
                        search: {$this->getController()->buildJsMethodCallFromView('onLoadData', $this)},
                        placeholder: "{$this->getWidget()->getQuickSearchPlaceholder()}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    
JS;
    }
        
    /**
     * Returns the data widget.
     * 
     * Override this method to use the trait to render iUseData widgets (like Chart),
     * because their getWidget() method would not return Data, but the visualizer
     * widget.
     * 
     * @return Data
     */
    protected function getDataWidget() : Data
    {
        return $this->getWidget();
    }
}