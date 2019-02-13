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
use exface\Core\Interfaces\Widgets\iShowImage;
use exface\OpenUI5Template\Templates\Elements\Traits\ui5DataElementTrait;

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
    use ui5DataElementTrait;
    
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
        $widget = $this->getWidget();
        $controller = $this->getController();
        
        $this->getPaginatorElement()->registerControllerMethods();
        
        $controller->addMethod('onUpdateFilterSummary', $this, '', $this->buildJsFilterSummaryUpdater());
        $controller->addMethod('onLoadData', $this, 'oControlEvent, keep_page_pos, growing', $this->buildJsDataLoader());
        $this->initConfiguratorControl($controller);
        $controller->addOnShowViewScript($this->buildJsRefresh());
        
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
        
        $js = $this->buildJsConstructorForTable();
        
        $initConfigModel = ".setModel(new sap.ui.model.json.JSONModel(), '{$this->getModelNameForConfigurator()}')";
        
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js) . $initConfigModel;
        } else {
            return $js . $initConfigModel;
        }
    }
    
    /**
     * 
     * @param string $oControllerJs
     * @return string
     */
    protected function buildJsConstructorForTable(string $oControllerJs = 'oController') : string
    {
        if ($this->isMTable()) {
            $js = $this->buildJsConstructorForMTable($oControllerJs);
        } else {
            $js = $this->buildJsConstructorForUiTable($oControllerJs);
        }
        
        return $js;
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
    protected function buildJsConstructorForMTable(string $oControllerJs = 'oController')
    {
        $mode = $this->getWidget()->getMultiSelect() ? 'sap.m.ListMode.MultiSelect' : 'sap.m.ListMode.SingleSelectMaster';
        $striped = $this->getWidget()->getStriped() ? 'true' : 'false';
        
        return <<<JS
        new sap.m.VBox({
            items: [
                new sap.m.Table("{$this->getId()}", {
            		fixedLayout: false,
                    alternateRowColors: {$striped},
                    noDataText: "{$this->translate('WIDGET.DATATABLE.NO_DATA_HINT')}",
            		itemPress: {$this->getController()->buildJsEventHandler($this, 'change')},
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
                {$this->buildJsClickListeners('oController')}
                {$this->buildJsPseudoEventHandlers()}
                ,
                {$this->buildJsConstructorForMTableFooter()}
            ]
        })
        
JS;
    }
    
    protected function buildJsConstructorForMTableFooter(string $oControllerJs = 'oController') : string
    {
        $visible = $this->getWidget()->isPaged() === false || $this->getWidget()->getHideFooter() === true ? 'false' : 'true';
        return <<<JS
                new sap.m.OverflowToolbar({
                    design: "Info",
                    visible: {$visible},
    				content: [
                        {$this->getPaginatorElement()->buildJsConstructor($oControllerJs)},
                        new sap.m.ToolbarSpacer(),
                        {$this->buildJsConfiguratorButtonConstructor($oControllerJs, 'Transparent')}
                    ]
                })
                
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
    protected function buildJsConstructorForUiTable(string $oControllerJs = 'oController')
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
                rowSelectionChange: {$controller->buildJsEventHandler($this, 'change')},
        		toolbar: [
        			{$this->buildJsToolbar($oControllerJs, $this->getPaginatorElement()->buildJsConstructor($oControllerJs))}
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
                    $js .= $this->buildJsDataLoaderFromLocal($oControlEventJsVar, $keepPagePosJsVar, $growingJsVar);
                } else {
                    $js .= $this->buildJsDataLoaderFromServer($oControlEventJsVar, $keepPagePosJsVar, $growingJsVar);
                }
                
                return $js;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsDataLoaderFromLocal($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos', $growingJsVar = 'growing')
    {
        $widget = $this->getWidget();
        $data = $widget->prepareDataSheetToRead($widget->getValuesDataSheet());
        if (! $data->isFresh()) {
            $data->dataRead();
        }
        
        // FIXME make filtering, sorting, pagination, etc. work in lazy mode too!
        
        return <<<JS
        
                try {
        			var data = {$this->getTemplate()->encodeData($this->getTemplate()->buildResponseData($data, $widget))};
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
    protected function buildJsDataLoaderFromServer($oControlEventJsVar = 'oControlEvent', $keepPagePosJsVar = 'keep_page_pos', $growingJsVar = 'growing')
    {
        $widget = $this->getWidget();
        
        $paginationSwitch = $widget->isPaged() ? 'true' : 'false';
        
        if ($widget->isPreloadDataEnabled()) {
            $doLoad = $this->buildJsDataLoaderFromServerPreload('oModel', 'params', $growingJsVar);
        } else {
            $doLoad = $this->buildJsDataLoaderFromServerRemote('oModel', 'params', $growingJsVar);
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
                
                {$this->buildJsDataSourceColumnActions($oControlEventJsVar, 'params')}
                
                // Add sorters and filters from P13nDialog
                var aSortItems = sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}').getSortItems();
                for (var i in aSortItems) {
                    params.sort = (params.sort ? params.sort+',' : '') + aSortItems[i].getColumnKey();
                    params.order = (params.order ? params.order+',' : '') + (aSortItems[i].getOperation() == 'Ascending' ? 'asc' : 'desc');
                }
                
                {$doLoad}
                
JS;
    }
                
    protected function buildJsQuickSearch(string $sQueryJs = 'sQuery', string $oRowJs = 'oRow') : string
    {
        $filters = [];
        foreach ($this->getWidget()->getAttributesForQuickSearch() as $alias) {
            $filters[] = "(oRow['{$alias}'].toString().toLowerCase().indexOf({$sQueryJs}) !== -1)";
        }
        
        if (! empty($filters)) {
            return implode(' || ', $filters);
        }
        
        return 'true';
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
                
    protected function buildJsDataLoaderFromServerRemote(string $oModelJs = 'oModel', string $oParamsJs = 'params', string $growingJsVar = 'growing') : string
    {
        return <<<JS

                var fnCompleted = function(oEvent){
                    {$this->buildJsBusyIconHide()}
        			if (oEvent.getParameters().success) {
                        {$this->buildJsDataLoaderOnLoaded('this', $growingJsVar)}
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
    
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel', string $growingJsVar = 'growing') : string
    {
        $paginator = $this->getPaginatorElement();
        
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
            
            if ({$growingJsVar}) {
                var oDataNew = {$oModelJs}.getData();
                oDataNew.data = oData.data.concat(oDataNew.data);
            }
            {$paginator->buildJsSetTotal($oModelJs . '.getProperty("/recordsFiltered")', 'oController')};
            {$paginator->buildJsRefresh('oController')};
            
            {$dynamicPageFixes}
            
			var footerRows = {$oModelJs}.getProperty("/footerRows");
            if (footerRows){
				oTable.setFixedBottomRowCount(parseInt(footerRows));
			}
			
JS;
    }
    
    protected function buildJsDataSourceColumnActions($oControlEventJsVar = 'oControlEvent', string $oParamsJs = 'params')
    {
        if ($this->isUiTable() === false) {
            return '';
        }
        
        return <<<JS
        
        // Add filters and sorters from column menus
		for (var i=0; i<oTable.getColumns().length; i++){
			var oColumn = oTable.getColumns()[i];
			if (oColumn.getFiltered()){
				{$oParamsJs}['{$this->getTemplate()->getUrlFilterPrefix()}' + oColumn.getFilterProperty()] = oColumn.getFilterValue();
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
			{$oParamsJs}['{$this->getTemplate()->getUrlFilterPrefix()}' + {$oControlEventJsVar}.getParameters().column.getFilterProperty()] = {$oControlEventJsVar}.getParameters().value;
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
     *
     * @return ui5DataPaginator
     */
    protected function getPaginatorElement() : ui5DataPaginator
    {
        return $this->getTemplate()->getElement($this->getWidget()->getPaginator());
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
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsValueGetter()
     */
    public function buildJsValueGetter($column = null, $rowNr = null)
    {
        if ($this->isUiTable()) {
            $row = "(oTable.getSelectedIndex() > -1 ? oTable.getModel().getData().data[oTable.getSelectedIndex()] : [])";
        } else {
            $row = "(oTable.getSelectedItem() ? oTable.getSelectedItem().getBindingContext().getObject() : [])";
        }
        
        $col = $column !== null ? '["' . $column . '"]' : '';
        
        return <<<JS
        
function(){
    var oTable = sap.ui.getCore().byId('{$this->getId()}');
    return {$row}{$col};
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
     * Empties the table by replacing it's model by an empty object.
     * 
     * @return string
     */
    protected function buildJsDataResetter() : string
    {
        return "sap.ui.getCore().byId('{$this->getId()}').getModel().setData({})";   
    }
}