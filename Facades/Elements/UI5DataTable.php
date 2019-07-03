<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Actions\iReadData;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataTableTrait;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Widgets\Data;
use exface\Core\Widgets\DataTableResponsive;
use exface\Core\Widgets\MenuButton;
use exface\UI5Facade\Facades\Elements\Traits\UI5DataElementTrait;
use exface\Core\Widgets\DataColumn;
use exface\Core\Widgets\DataButton;

/**
 *
 * @method DataTable getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class UI5DataTable extends UI5AbstractElement
{
    use JqueryDataTableTrait;
    
    use ui5DataElementTrait {
       buildJsDataLoaderOnLoaded as buildJsDataLoaderOnLoadedViaTrait;
       buildJsConstructor as buildJsConstructorViaTrait;
       getCaption as getCaptionViaTrait;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->getPaginatorElement()->registerControllerMethods();
        return $this->buildJsConstructorViaTrait($oControllerJs);
    }
    
    protected function buildJsConstructorForControl($oControllerJs = 'oController') : string
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
                        {$this->buildJsToolbar($oControllerJs)}
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
            $column_defs .= ($column_defs ? ", " : '') . $this->getFacade()->getElement($column)->buildJsConstructorForUiColumn();
        }
        
        return $column_defs;
    }
    
    protected function buildJsCellsForMTable()
    {
        $cells = '';
        foreach ($this->getWidget()->getColumns() as $column) {
            $cells .= ($cells ? ", " : '') . $this->getFacade()->getElement($column)->buildJsConstructorForCell();
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
            $column_defs .= ($column_defs ? ", " : '') . $this->getFacade()->getElement($column)->buildJsConstructorForMColumn();
        }
        
        return $column_defs;
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
        			var data = {$this->getFacade()->encodeData($this->getFacade()->buildResponseData($data, $widget))};
        		} catch (err){
                    console.error('Cannot load data into widget {$this->getId()}!');
                    return;
        		}
                sap.ui.getCore().byId("{$this->getId()}").getModel().setData(data);
                
JS;
    }
                
    protected function buildJsQuickSearch(string $sQueryJs = 'sQuery', string $oRowJs = 'oRow') : string
    {
        $filters = [];
        foreach ($this->getWidget()->getAttributesForQuickSearch() as $attr) {
            $filters[] = "(oRow['{$attr->getAliasWithRelationPath()}'].toString().toLowerCase().indexOf({$sQueryJs}) !== -1)";
        }
        
        if (! empty($filters)) {
            return implode(' || ', $filters);
        }
        
        return 'true';
    }
    
    protected function buildJsDataLoaderParams(string $oControlEventJsVar = 'oControlEvent', string $oParamsJs = 'params', $keepPagePosJsVar = 'keep_page_pos') : string
    {
        $paginationSwitch = $this->getWidget()->isPaged() ? 'true' : 'false';
        
        $commonParams = <<<JS

        		// Add pagination
                if ({$paginationSwitch}) {
                    var paginator = {$this->getPaginatorElement()->buildJsGetPaginator('oController')};
                    if (! {$keepPagePosJsVar}) {
                        paginator.resetAll();
                    }
                    {$oParamsJs}.start = paginator.start;
                    {$oParamsJs}.length = paginator.pageSize;
                }

JS;
        
        if ($this->isUiTable() === false) {
            return $commonParams;
        }
        
        return $commonParams . <<<JS
        
        // Add filters and sorters from column menus
		for (var i=0; i<oTable.getColumns().length; i++){
			var oColumn = oTable.getColumns()[i];
			if (oColumn.getFiltered()){
				{$oParamsJs}['{$this->getFacade()->getUrlFilterPrefix()}' + oColumn.getFilterProperty()] = oColumn.getFilterValue();
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
			{$oParamsJs}['{$this->getFacade()->getUrlFilterPrefix()}' + {$oControlEventJsVar}.getParameters().column.getFilterProperty()] = {$oControlEventJsVar}.getParameters().value;
		}
		
JS;
    }
    
    /**
     * Returns inline JS code to refresh the table.
     *
     * If the code snippet is to be used somewhere, where the controller is directly accessible, you can pass the
     * name of the controller variable to $oControllerJsVar to increase performance.
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsRefresh()
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
    
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($action === null) {
            $rows = "sap.ui.getCore().byId('{$this->getId()}').getModel().getData().data";
        } elseif ($action instanceof iReadData) {
            // If we are reading, than we need the special data from the configurator
            // widget: filters, sorters, etc.
            return $this->getFacade()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsDataGetter($action);
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
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetter()
     */
    public function buildJsValueGetter($dataColumnName = null, $rowNr = null)
    {
        if ($this->isUiTable()) {
            $row = "(oTable.getSelectedIndex() > -1 ? oTable.getModel().getData().data[oTable.getSelectedIndex()] : [])";
        } else {
            $row = "(oTable.getSelectedItem() ? oTable.getSelectedItem().getBindingContext().getObject() : [])";
        }
        
        $col = $dataColumnName !== null ? '["' . $dataColumnName . '"]' : '';
        
        return <<<JS
        
function(){
    var oTable = sap.ui.getCore().byId('{$this->getId()}');
    return {$row}{$col};
}()

JS;
    }
        
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetter()
     */
    public function buildJsValueSetter($value, $dataColumnName = null, $rowNr = null)
    {
        if ($rowNr === null) {
            if ($this->isUiTable()) {
                $rowNr = "oTable.getSelectedIndex()";
            } else {
                $rowNr = "oTable.indexOfItem(oTable.getSelectedItem())";
            }
        }
        
        if ($dataColumnName === null) {
            $dataColumnName = $this->getWidget()->getUidColumn()->getDataColumnName();
        }
        
        return <<<JS
        
function(){
    var oTable = sap.ui.getCore().byId('{$this->getId()}');
    var oModel = oTable.getModel();
    var iRowIdx = {$rowNr};
    
    if (iRowIdx !== undefined && iRowIdx >= 0) {
        var aData = oModel.getData().data;
        aData[iRowIdx]["{$dataColumnName}"] = $value;
        oModel.setProperty("/data", aData);
        // TODO why does the code below not work????
        // oModel.setProperty("/data(" + iRowIdx + ")/{$dataColumnName}", {$value});
    }
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
        		{$this->getFacade()->getElement($dblclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })
JS;
        }
        
        // Right click. Currently only supports one double click action - the first one in the list of buttons
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $rightclick_script = $this->getFacade()->getElement($rightclick_button)->buildJsClickEventHandlerCall($oControllerJsVar);
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
        		{$this->getFacade()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
            })
JS;
            } else {
                $js .= <<<JS
                
            .attachItemPress(function(oEvent) {
                {$this->getFacade()->getElement($leftclick_button)->buildJsClickEventHandlerCall($oControllerJsVar)};
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
        
        /* @var $btn_element \exface\UI5Facade\Facades\Elements\UI5Button */
        $btn_element = $this->getFacade()->getElement($button);
        
        if ($button instanceof MenuButton){
            if ($button->getParent() instanceof ButtonGroup && $button === $this->getFacade()->getElement($button->getParent())->getMoreButtonsMenu()){
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
     * Empties the table by replacing it's model by an empty object.
     * 
     * @return string
     */
    protected function buildJsDataResetter() : string
    {
        return "sap.ui.getCore().byId('{$this->getId()}').getModel().setData({})";   
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see ui5DataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $oModelJs = 'oModel') : string
    {
        $paginator = $this->getPaginatorElement();
        
        // Add single-result action to onLoadSuccess
        if ($singleResultButton = $this->getWidget()->getButtons(function($btn) {return ($btn instanceof DataButton) && $btn->isBoundToSingleResult() === true;})[0]) {
            $singleResultJs = <<<JS
            if ({$oModelJs}.getData().data.length === 1) {
                var curRow = {$oModelJs}.getData().data[0];
                var lastRow = oTable._singleResultActionPerformedFor;
                if (lastRow === undefined || {$this->buildJsRowCompare('curRow', 'lastRow')} === false){
                    {$this->buildJsSelectRowByIndex('oTable', '0')}
                    oTable._singleResultActionPerformedFor = curRow;
                    {$this->getFacade()->getElement($singleResultButton)->buildJsClickEventHandlerCall('oController')};
                } else {
                    oTable._singleResultActionPerformedFor = {};
                }
            }
                        
JS;
        }
        
        return $this->buildJsDataLoaderOnLoadedViaTrait($oModelJs) . <<<JS

			var footerRows = {$oModelJs}.getProperty("/footerRows");
            if (footerRows){
				oTable.setFixedBottomRowCount(parseInt(footerRows));
			}

            {$paginator->buildJsSetTotal($oModelJs . '.getProperty("/recordsFiltered")', 'oController')};
            {$paginator->buildJsRefresh('oController')};  

            {$singleResultJs}          
            
JS;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see ui5DataElementTrait::buildJsDataLoaderPrepare()
     */
    protected function buildJsDataLoaderPrepare() : string
    {
        if ($this->isMTable()) {
            return "sap.ui.getCore().byId('{$this->getId()}').setNoDataText('{$this->translate('WIDGET.DATATABLE.NO_DATA_HINT')}');";
        }
        
        return '';
    }
    
    /**
     *
     * {@inheritdoc}
     * @see ui5DataElementTrait::buildJsOfflineHint()
     */
    protected function buildJsOfflineHint(string $oTableJs = 'oTable') : string
    {
        if ($this->isMTable()) {
            return $oTableJs . ".setNoDataText('{$this->translate('WIDGET.DATATABLE.OFFLINE_HINT')}');";
        }
        
        return '';
    }
    
    /**
     *
     * @return ui5DataPaginator
     */
    protected function getPaginatorElement() : ui5DataPaginator
    {
        return $this->getFacade()->getElement($this->getWidget()->getPaginator());
    }
    
    /**
     *
     * @return bool
     */
    protected function hasPaginator() : bool
    {
        return ($this->getWidget() instanceof Data) && $this->getWidget()->isPaged();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see ui5DataElementTrait::getCaption()
     */
    public function getCaption() : string
    {
        if ($caption = $this->getCaptionViaTrait()) {
            $caption .= ($this->hasPaginator() ? ': ' : '');
        }
        return $caption;
    }
    
    /**
     * Returns the JS code to select the row with the zero-based index $iRowIdxJs and scroll it into view.
     * 
     * @param string $oTableJs
     * @param string $iRowIdxJs
     * @return string
     */
    protected function buildJsSelectRowByIndex(string $oTableJs = 'oTable', string $iRowIdxJs = 'iRowIdx') : string
    {
        if ($this->isMTable() === true) {
            return <<<JS

                var oItem = {$oTableJs}.getItems()[{$iRowIdxJs}];
                {$oTableJs}.setSelectedItem(oItem);
                oItem.focus();

JS;
        } else {
            return <<<JS

                oTable.setFirstVisibleRow({$iRowIdxJs});
                oTable.setSelectedIndex({$iRowIdxJs});

JS;
        }
    }
    
    /**
     * Returns JS code to select the first row in a table, that has the given value in the specified column.
     *
     * The generated code will search the current values of the $column for an exact match
     * for the value of $valueJs JS variable, mark the first matching row as selected and
     * scroll to it to ensure it is visible to the user.
     *
     * The row index (starting with 0) is saved to the JS variable specified in $rowIdxJs.
     *
     * If the $valueJs is not found, $onNotFoundJs will be executed and $rowIdxJs will be
     * set to -1.
     *
     * @param DataColumn $column
     * @param string $valueJs
     * @param string $onNotFoundJs
     * @param string $rowIdxJs
     * @return string
     */
    public function buildJsSelectRowByValue(DataColumn $column, string $valueJs, string $onNotFoundJs = '', string $rowIdxJs = 'rowIdx') : string
    {
        return <<<JS
        
var {$rowIdxJs} = function() {
    var oTable = sap.ui.getCore().byId("{$this->getId()}");
    var aData = oTable.getModel().getData().data;
    var iRowIdx = -1;
    for (var i in aData) {
        if (aData[i]['{$column->getDataColumnName()}'] == $valueJs) {
            iRowIdx = i;
        }
    }

    if (iRowIdx == -1){
		{$onNotFoundJs};
	} else {
        {$this->buildJsSelectRowByIndex('oTable', 'iRowIdx')}
	}

    return iRowIdx;
}();

JS;
    }
}