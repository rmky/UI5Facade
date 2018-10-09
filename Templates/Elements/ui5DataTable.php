<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Actions\iReadData;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Widgets\MenuButton;
use exface\Core\Widgets\DataTableResponsive;

/**
 *
 * @method DataTable getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5DataTable extends ui5AbstractElement
{
    use JqueryDataTableTrait;
    
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
        $controller = $this->getController();
        
        $this->getPaginatorElement()->registerControllerMethods();
        
        $controller->addMethod('onUpdateFilterSummary', $this, '', $this->buildJsFilterSummaryUpdater());
        $controller->addMethod('onLoadData', $this, 'oControlEvent, keep_page_pos, growing', $this->buildJsDataLoader());
        $controller->addDependentControl('oConfigurator', $this, $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget()));
        $controller->addOnShowViewScript($this->buildJsRefresh());
        
        if ($this->isMTable()) {
            $js = $this->buildJsConstructorForMTable();
        } else {
            $js = $this->buildJsConstructorForUiTable();
        }
        
        $js .= <<<JS
        {$this->buildJsPseudoEventHandlers()}

JS;
        
        $initConfigModel = ".setModel(new sap.ui.model.json.JSONModel(), '{$this->getModelNameForConfigurator()}')";
        
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js) . $initConfigModel;
        } else {
            return $js . $initConfigModel;
        }
    }
    
    protected function isMTable()
    {
        return $this->getWidget() instanceof DataTableResponsive;
    }
    
    protected function isUiTable()
    {
        return ! ($this->getWidget() instanceof DataTableResponsive);
    }
    
    /**
     * Returns the javascript constructor for a sap.m.Table
     * 
     * @return string
     */
    protected function buildJsConstructorForMTable()
    {
        $mode = $this->getWidget()->getMultiSelect() ? 'sap.m.ListMode.MultiSelect' : 'sap.m.ListMode.SingleSelectMaster';
        $striped = $this->getWidget()->getStriped() ? 'true' : 'false';
        
        return <<<JS
        new sap.m.Table("{$this->getId()}", {
    		fixedLayout: false,
            alternateRowColors: {$striped},
            noDataText: "{$this->translate('WIDGET.DATATABLE.NO_DATA_HINT')}",
    		mode: {$mode},
            headerToolbar: [
                {$this->buildJsToolbar()}
    		],
    		columns: [
                {$this->buildJsColumnsForMTable()}
    		],
    		items: {
    			path: '/data',
                {$this->buildJsBindingOptionsForGrouping()}
                template: new sap.m.ColumnListItem({
                    type: "Active",
                    cells: [
                        {$this->buildJsCellsForMTable()}
                    ]
                }),
    		}
        })
        .setModel(new sap.ui.model.json.JSONModel())
        .attachItemPress(function(event){
            {$this->getOnChangeScript()}
        }){$this->buildJsClickListeners('oController')}

JS;
    }
            
    protected function buildJsBindingOptionsForGrouping()
    {
        $widget = $this->getWidget();
        
        if (! $widget->hasRowGroups()) {
            return '';
        }
        
        return <<<JS

                sorter: new sap.ui.model.Sorter(
    				'{$widget->getRowGrouper()->getGroupByColumn()->getDataColumnName()}', // sPath
    				false, // bDescending
    				true // vGroup
    			),
    			/*groupHeaderFactory: function(oGroup) {
                    // TODO add support for counters
                    return new sap.m.GroupHeaderListItem({
        				title: oGroup.key,
        				upperCase: false
        			});
                },*/
JS;
    }
    
    /**
     * Returns the javascript constructor for a sap.ui.table.Table
     * 
     * @return string
     */
    protected function buildJsConstructorForUiTable()
    {
        $widget = $this->getWidget();
        $controller = $this->getController();
        
        $selection_mode = $widget->getMultiSelect() ? 'sap.ui.table.SelectionMode.MultiToggle' : 'sap.ui.table.SelectionMode.Single';
        $selection_behavior = $widget->getMultiSelect() ? 'sap.ui.table.SelectionBehavior.Row' : 'sap.ui.table.SelectionBehavior.RowOnly';
        
        $js = <<<JS
            new sap.ui.table.Table("{$this->getId()}", {
        		visibleRowCountMode: sap.ui.table.VisibleRowCountMode.Auto,
                selectionMode: {$selection_mode},
        		selectionBehavior: {$selection_behavior},
                enableColumnReordering:true,
                enableColumnFreeze: true,
        		filter: {$controller->buildJsMethodCallFromView('onLoadData', $this)},
        		sort: {$controller->buildJsMethodCallFromView('onLoadData', $this)},
        		toolbar: [
        			{$this->buildJsToolbar()}
        		],
        		columns: [
        			{$this->buildJsColumnsForUiTable()}
        		],
                rows: "{/data}"
        	})
            .setModel(new sap.ui.model.json.JSONModel())
            {$this->buildJsScrollHandlerForUiTable()}
            {$this->buildJsClickListeners('oController')}
JS;
            
        return $js;
    }
    
    protected function buildJsScrollHandlerForUiTable() : string
    {
        return <<<JS

            .attachFirstVisibleRowChanged(function(oEvent) {
                var oTable = oEvent.getSource();
                var oPaginator = {$this->getPaginatorElement()->buildJsGetPaginator('oController')};
                var lastVisibleRow = oTable.getFirstVisibleRow() + oTable.getVisibleRowCount();
                if ((oPaginator.pageSize - lastVisibleRow <= 1) && (oPaginator.end() + 1 !== oPaginator.total)) {
                    oPaginator.increasePageSize();
                    {$this->buildJsRefresh(true, true)}
                }
            })
JS;
    }
    
    /**
     * Returns a comma separated list of column constructors for sap.ui.table.Table
     * 
     * @return string
     */
    protected function buildJsColumnsForUiTable()
    {
        // Columns
        $column_defs = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $column_defs .= ($column_defs ? ", " : '') . $this->getTemplate()->getElement($column)->buildJsConstructorForUiColumn();
        }
        
        return $column_defs;
    }
    
    protected function buildJsCellsForMTable()
    {
        $cells = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $cells .= ($cells ? ", " : '') . $this->getTemplate()->getElement($column)->buildJsConstructorForCell();
        }
        
        return $cells;
    }
    
    /**
     * Returns a comma-separated list of column constructors for sap.m.Table
     * 
     * @return string
     */
    protected function buildJsColumnsForMTable()
    {
        $widget = $this->getWidget();
        
        // See if there are promoted columns. If not, make the first visible column promoted,
        // because sap.m.table would otherwise have not column headers at all.
        $promotedFound = false;
        $first_col = null;
        foreach ($widget->getColumns() as $col) {
            if (is_null($first_col) && ! $col->isHidden()) {
                $first_col = $col;    
            }
            if ($col->getVisibility() === EXF_WIDGET_VISIBILITY_PROMOTED && ! $col->isHidden()) {
                $promotedFound = true;
                break;
            }
        }
        
        if (! $promotedFound) {
            $first_col->setVisibility(EXF_WIDGET_VISIBILITY_PROMOTED);
        }

        $column_defs = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $column_defs .= ($column_defs ? ", " : '') . $this->getTemplate()->getElement($column)->buildJsConstructorForMColumn();
        }
        
        return $column_defs;
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
     * Returns the definition of a javascript function to fill the table with data: onLoadDataTableId(oControlEvent).
     *  
     * @return string
     */
    protected function buildJsDataLoader($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos', $growingJsVar = 'growing')
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
            $js .= $this->buildJsDataLoaderOnClient($oControlEventJsVar, $keepPagePosJsVar, $growingJsVar);
        } else {
            $js .= $this->buildJsDataLoaderOnServer($oControlEventJsVar, $keepPagePosJsVar, $growingJsVar);
        } 
        
        return $js;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataLoaderOnClient($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos', $growingJsVar = 'growing')
    {
        $widget = $this->getWidget();
        $data = $widget->prepareDataSheetToRead($widget->getValuesDataSheet());
        if (! $data->isFresh()) {
            $data->dataRead();
        }
        
        // FIXME make filtering, sorting, pagination, etc. work in lazy mode too!
        
        return <<<JS

                try {
        			var data = {$this->getTemplate()->encodeData($this->prepareData($data, false))};
        		} catch (err){
                    console.error('Cannot load data into widget {$this->getId()}!');
                    return;
        		}
                sap.ui.getCore().byId("{$this->getId()}").getModel().setData(data);
    
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataLoaderOnServer($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos', $growingJsVar = 'growing')
    {
        $widget = $this->getWidget();
        $controller = $this->getController();
        
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
            
        $paginationSwitch = $widget->isPaged() ? 'true' : 'false';
        $paginator = $this->getPaginatorElement();
        
        $url = $this->getAjaxUrl();
        $params = '
					action: "' . $widget->getLazyLoadingActionAlias() . '"
					, resource: "' . $this->getPageId() . '"
					, element: "' . $widget->getId() . '"
					, object: "' . $widget->getMetaObject()->getId() . '"
				';
        
        return <<<JS

        		var oTable = sap.ui.getCore().byId("{$this->getId()}");
                var params = { {$params} };
        		var cols = oTable.getColumns();
        		var oModel = oTable.getModel();
                var oData = oModel.getData(); 
                var oController = this;
                
                oModel.attachRequestSent(function(){
        			{$this->buildJsBusyIconShow()}
        		});

                var fnCompleted = function(oEvent){
                    {$this->buildJsBusyIconHide()}
        			if (oEvent.getParameters().success) {
                        if (growing) {
                            var oDataNew = this.getData();
                            oDataNew.data = oData.data.concat(oDataNew.data);
                        }
                        {$paginator->buildJsSetTotal('this.getProperty("/recordsFiltered")', 'oController')};
                        {$paginator->buildJsRefresh('oController')};
                        
                        {$dynamicPageFixes}                     

            			var footerRows = this.getProperty("/footerRows");
                        if (footerRows){
            				oTable.setFixedBottomRowCount(parseInt(footerRows));
            			}
                    } else {
                        var error = oEvent.getParameters().errorobject;
                        if (! navigator.onLine) {
                            if (oData.length = 0) { 
                                {$this->buildJsOfflineHint('oTable')}
                            } else {
                                {$this->buildJsShowError("'{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR')}'", "'{$this->translate('WIDGET.DATATABLE.OFFLINE_ERROR_TITLE')}'")}
                            }
                        } else {
                            {$this->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
                        }
                    }
                    
                    oTable.getModel("{$this->getModelNameForConfigurator()}").setProperty('/filterDescription', {$controller->buildJsMethodCallFromController('onUpdateFilterSummary', $this, '', 'oController')});
                    this.detachRequestCompleted(fnCompleted);
        		};
        
        		oModel.attachRequestCompleted(fnCompleted);
        		
        		// Add quick search
                params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();
                
                // Add configurator data
                params.data = {$this->getP13nElement()->buildJsDataGetter()};
                
        		// Add pagination
                if ({$paginationSwitch}) {
                    var paginator = {$this->getPaginatorElement()->buildJsGetPaginator('oController')};
                    if (! {$keepPagePosJsVar}) {
                        paginator.resetAll();
                    }
                    if ({$growingJsVar}) {
                        params.start = paginator.growingLoadStart();
                        params.length = paginator.growingLoadPageSize();
                    } else {
                        params.start = paginator.start;
                        params.length = paginator.pageSize;
                    }
                }
        
                {$this->buildJsDataSourceColumnActions($oControlEventJsVar)}
                
                // Add sorters and filters from P13nDialog
                var aSortItems = sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}').getSortItems();
                for (var i in aSortItems) {
                    params.sort = (params.sort ? params.sort+',' : '') + aSortItems[i].getColumnKey();
                    params.order = (params.order ? params.order+',' : '') + (aSortItems[i].getOperation() == 'Ascending' ? 'asc' : 'desc');
                }
                
                oModel.loadData("{$url}", params);
    
JS;
    }
    
    protected function buildJsDataSourceColumnActions($oControlEventJsVar = 'oControlEvent')
    {
        if ($this->isMTable()) {
            return '';
        }
        
        return <<<JS

        // Add filters and sorters from column menus
		for (var i=0; i<oTable.getColumns().length; i++){
			var oColumn = oTable.getColumns()[i];
			if (oColumn.getFiltered()){
				params['{$this->getTemplate()->getUrlFilterPrefix()}' + oColumn.getFilterProperty()] = oColumn.getFilterValue();
			}
		}
		
		// If sorting just now, make sure the sorter from the event is set too (eventually overwriting the previous sorting)
		if ({$oControlEventJsVar} && {$oControlEventJsVar}.getId() == 'sort'){
            sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}')
                .destroySortItems()
                .addSortItem(
                    new sap.m.P13nSortItem({
                        columnKey: {$oControlEventJsVar}.getParameters().column.getSortProperty(),
                        operation: {$oControlEventJsVar}.getParameters().sortOrder
                    })
                );
		}
		
		// If filtering just now, make sure the filter from the event is set too (eventually overwriting the previous one)
		if ({$oControlEventJsVar} && {$oControlEventJsVar}.getId() == 'filter'){
			params['{$this->getTemplate()->getUrlFilterPrefix()}' + {$oControlEventJsVar}.getParameters().column.getFilterProperty()] = {$oControlEventJsVar}.getParameters().value;
		}

JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsFilterSummaryUpdater()
    {
        $filter_checks = '';
        foreach ($this->getWidget()->getFilters() as $fltr) {
            if ($fltr->isHidden()) {
                continue;
            }
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
     * Returns the constructor for the table's main toolbar (OverflowToolbar).
     * 
     * The toolbar contains the paginator, all the action buttons, the quick search
     * and the button for the personalization dialog as well as the P13nDialog itself.
     * 
     * The P13nDialog is appended to the toolbar wrapped in an invisible container in
     * order not to affect the overflow behavior. The dialog must be included in the
     * toolbar to ensure it is destroyed with the toolbar and does not become an
     * orphan (e.g. when the view containing the table is destroyed).
     * 
     * @return string
     */
    protected function buildJsToolbar($oControllerJsVar = 'oController')
    {
        $controller = $this->getController();
        $heading = $this->buildTextTableHeading() . ($this->getWidget()->isPaged() ? ': ' : '');
        $heading = $this->isWrappedInDynamicPage() ? '' : 'new sap.m.Label({text: "' . $heading . '"}),';
        
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$heading}
			        {$this->getPaginatorElement()->buildJsConstructor($oControllerJsVar)}
                    new sap.m.ToolbarSpacer(),
                    {$this->buildJsButtonsConstructors()}
					new sap.m.SearchField("{$this->getId()}_quickSearch", {
                        width: "200px",
                        search: {$controller->buildJsMethodCallFromView('onLoadData', $this)},
                        placeholder: "{$this->getQuickSearchPlaceholder(false)}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://drop-down-list",
                        text: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        tooltip: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),
                        press: function() {
                			{$controller->buildJsDependentControlSelector('oConfigurator', $this, $oControllerJsVar)}.open();
                		}
                    })		
				]
			})
JS;
        return $toolbar;
    }
    
    /**
     * 
     * @return ui5DataPaginator
     */
    protected function getPaginatorElement() : ui5DataPaginator
    {
        return $this->getTemplate()->getElement($this->getWidget()->getPaginator());
    }
    
    /**
     * Returns the text to be shown a table title
     * 
     * @return string
     */
    protected function buildTextTableHeading()
    {
        $widget = $this->getWidget();
        return $widget->getCaption() ? $widget->getCaption() : $widget->getMetaObject()->getName();
    }
    
    /**
     * Returns inline JS code to refresh the table.
     * 
     * If the code snippet is to be used somewhere, where the controller is directly accessible, you can pass the
     * name of the controller variable to $oControllerJsVar to increase performance.
     * 
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsRefresh()
     * 
     * @param bool $keep_page_pos
     * @param bool $growing
     * @param string $oControllerJsVar
     * 
     * @return ui5DataTable
     */
    public function buildJsRefresh($keep_page_pos = false, $growing = false, string $oControllerJsVar = null)
    {
        $params = "undefined, " . ($keep_page_pos ? 'true' : 'false') . ', ' . ($growing ? 'true' : 'false');
        if ($oControllerJsVar === null) {
            return $this->getController()->buildJsMethodCallFromController('onLoadData', $this, $params);
        } else {
            return $this->getController()->buildJsMethodCallFromController('onLoadData', $this, $params, $oControllerJsVar);
        }
    }
    
    /**
     * Returns a ready-to-use comma separated list of javascript constructors for all buttons of the table.
     * 
     * @return string
     */
    protected function buildJsButtonsConstructors()
    {
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
     * Wraps the given content in a constructor for the sap.f.DynamicPage used to create the Fiori list report floorplan.
     * 
     * @param string $content
     * @return string
     */
    protected function buildJsPage($content)
    {  
        foreach ($this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions()->getButtons() as $btn) {
            if ($btn->getAction()->isExactly('exface.Core.RefreshWidget')){
                $btn->setHideButtonIcon(true);
                $btn->setHint($btn->getCaption());
                $btn->setCaption($this->translate('WIDGET.DATATABLE.GO_BUTTON_TEXT'));
            }
            $top_buttons .= $this->getTemplate()->getElement($btn)->buildJsConstructor() . ',';
        }
        return <<<JS

        new sap.f.DynamicPage("{$this->getIdOfDynamicPage()}", {
            fitContent: true,
            preserveHeaderStateOnScroll: true,
            headerExpanded: true,
            title: new sap.f.DynamicPageTitle({
				expandedHeading: [
                    new sap.m.Title({
                        text: "{$this->buildTextTableHeading()}"
                    })
				],
                snappedHeading: [
                    new sap.m.VBox({
                        items: [
        					new sap.m.Title({
                                text: "{$this->buildTextTableHeading()}"
                            }),
                            new sap.m.Text({
                                text: "{{$this->getModelNameForConfigurator()}>/filterDescription}"
                            })
                        ]
                    })
				],
                navigationActions: [
                    new sap.m.Button({
                        icon: "sap-icon://nav-back",
                        press: [oController.onNavBack, oController],
                        type: sap.m.ButtonType.Transparent
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
     * Returns TRUE if the table will be wrapped in a sap.f.DynamicPage to create a Fiori ListReport
     * 
     * @return boolean
     */
    protected function isWrappedInDynamicPage()
    {
        return $this->getWidget()->hasParent() || $this->getWidget()->getHideHeader() ? false : true;
    }
    
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($action === null) {
            $rows = "sap.ui.getCore().byId('{$this->getId()}').getModel().getData().data";
        } elseif ($action instanceof iReadData) {
            // If we are reading, than we need the special data from the configurator
            // widget: filters, sorters, etc.
            return $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsDataGetter($action);
        } elseif ($this->isEditable() && $action->implementsInterface('iModifyData')) {
            $rows = "oTable.getModel().getData().data";
        } else {
            if ($this->isUiTable()) {
                $rows = "(oTable.getSelectedIndex() > -1 ? [oTable.getModel().getData().data[oTable.getSelectedIndex()]] : [])";
            } else {
                $rows = "(oTable.getSelectedItem() ? [oTable.getSelectedItem().getBindingContext().getObject()] : [])";
            }
        }
        return <<<JS
    function() {
        var oTable = sap.ui.getCore().byId('{$this->getId()}');
        var rows = {$rows};
        return {
            oId: '{$this->getWidget()->getMetaObject()->getId()}', 
            rows: (rows === undefined ? [] : rows)
        };
    }()
JS;
    }
        
    protected function buildJsClickListeners($oControllerJsVar = 'oController')
    {
        $widget = $this->getWidget();
        
        $js = '';
        $rightclick_script = '';
        		
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $js .= <<<JS

            .attachBrowserEvent("dblclick", function(oEvent) {
        		{$this->getTemplate()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })
JS;
        }
        
        // Right click. Currently only supports one double click action - the first one in the list of buttons
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $rightclick_script = $this->getTemplate()->getElement($rightclick_button)->buildJsClickEventHandlerCall($oControllerJsVar);
        } else {
            $rightclick_script = $this->buildJsContextMenuTrigger();
        }
        
        if ($rightclick_script) {
            $js .= <<<JS
            
            .attachBrowserEvent("contextmenu", function(oEvent) {
                oEvent.preventDefault();
                {$rightclick_script}
        	})

JS;
        }
                
        // Single click. Currently only supports one click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            if ($this->isUiTable()) {
                $js .= <<<JS
                
            .attachBrowserEvent("click", function(oEvent) {
        		{$this->getTemplate()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })
JS;
            } else {
                $js .= <<<JS
                
            .attachItemPress(function(oEvent) {
                {$this->getTemplate()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })
JS;
            }
        }
        
        return $js;
    }
		
    protected function buildJsContextMenuTrigger($eventJsVar = 'oEvent') {
        return <<<JS
        
                var oMenu = {$this->buildJsContextMenu($this->getWidget()->getButtons())};
                var eFocused = $(':focus');
                var eDock = sap.ui.core.Popup.Dock;
                oMenu.open(true, eFocused, eDock.CenterCenter, eDock.CenterBottom,  {$eventJsVar}.target);
          
JS;
    }
	
    /**
     * 
     * @param Button[]
     * @return string
     */
    protected function buildJsContextMenu(array $buttons)
    {
        return <<<JS

                new sap.ui.unified.Menu({
                    items: [
                        {$this->buildJsContextMenuButtons($buttons)}
                    ]
                })
JS;
    }
        
    /**
     *
     * @param Button[] $buttons
     * @return string
     */
    protected function buildJsContextMenuButtons(array $buttons)
    {
        $context_menu_js = '';
        
        $last_parent = null;
        foreach ($buttons as $button) {
            if ($button->isHidden()) {
                continue;
            }
            if ($button->getParent() == $this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions()) {
                continue;
            }
            if (! is_null($last_parent) && $button->getParent() !== $last_parent) {
                $startSection = true;
            }
            $last_parent = $button->getParent();
            
            $context_menu_js .= ($context_menu_js ? ',' : '') . $this->buildJsContextMenuItem($button, $startSection);
        }
        
        return $context_menu_js;
    }
    
    /**
     * 
     * @param Button $button
     * @param boolean $startSection
     * @return string
     */
    protected function buildJsContextMenuItem(Button $button, $startSection = false)
    {
        $menu_item = '';
        
        $startsSectionProperty = $startSection ? 'startsSection: true,' : '';
        
        /* @var $btn_element \exface\OpenUI5template\Templates\Elements\ui5Button */
        $btn_element = $this->getTemplate()->getElement($button);
        
        if ($button instanceof MenuButton){
            if ($button->getParent() instanceof ButtonGroup && $button === $this->getTemplate()->getElement($button->getParent())->getMoreButtonsMenu()){
                $caption = $button->getCaption() ? $button->getCaption() : '...';
            } else {
                $caption = $button->getCaption();
            }
            $menu_item = <<<JS

                        new sap.ui.unified.MenuItem({
                            icon: "{$btn_element->buildCssIconClass($button->getIcon())}",
                            text: "{$caption}",
                            {$startsSectionProperty}
                            submenu: {$this->buildJsContextMenu($button->getButtons())}
                        })
JS;
        } else {
            $handler = $btn_element->buildJsClickViewEventHandlerCall();
            $select = $handler !== '' ? 'select: ' . $handler . ',' : '';
            $menu_item = <<<JS

                        new sap.ui.unified.MenuItem({
                            icon: "{$btn_element->buildCssIconClass($button->getIcon())}",
                            text: "{$button->getCaption()}",
                            {$select}
                            {$startsSectionProperty}
                        })
JS;
        }
        return $menu_item;
    }
    
    /**
     * 
     * @return ui5DataConfigurator
     */
    protected function getP13nElement()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget());
    }
    
    protected function getModelNameForConfigurator() : string
    {
        return 'configurator';
    }
    
    protected function getIdOfDynamicPage() : string
    {
        return $this->getId() . "_DynamicPageWrapper";
    }
    
    protected function buildJsDataLoaderPrepare() : string
    {
        if ($this->isMTable()) {
            return "sap.ui.getCore().byId('{$this->getId()}').setNoDataText('{$this->translate('WIDGET.DATATABLE.NO_DATA_HINT')}');";
        }
        
        return '';
    }
    
    protected function buildJsOfflineHint(string $oTableJs = 'oTable') : string
    {
        if ($this->isMTable()) {
            return $oTableJs . ".setNoDataText('{$this->translate('WIDGET.DATATABLE.OFFLINE_HINT')}');";
        }
        
        return '';
    }
}
?>