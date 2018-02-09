<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Actions\iReadData;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Widgets\MenuButton;

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
        if ($this->isWrappedInDynamicPage()) {
            $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->setIncludeFilterTab(false);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    function generateJs()
    {
        $buttons_functions = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $buttons_functions .= $this->getTemplate()->getElement($btn)->generateJs();
        }
        return <<<JS
    var {$this->getJsVar()};
    {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->generateJs()}
	{$this->buildJsDataSource()}
    {$this->buildJsPagination()}
    {$buttons_functions}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor()
    { 
        if ($this->isMTable()) {
            $js = $this->buildJsConstructorForMTable();
        } else {
            $js = $this->buildJsConstructorForUiTable();
        }
        
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js) . ".setModel(sap.ui.getCore().byId('{$this->getId()}').getModel())";
        } else {
            return $js;
        }
    }
    
    protected function isMTable()
    {
        return $this->getWidget()->isResponsive();
    }
    
    protected function isUiTable()
    {
        return ! $this->getWidget()->isResponsive();
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
        function() {
            var {$this->getJsVar()} = new sap.m.Table("{$this->getId()}", {
        		fixedLayout: false,
                alternateRowColors: {$striped},
        		mode: {$mode},
                headerToolbar: [
                    {$this->buildJsToolbar()}
        		],
        		columns: [
                    {$this->buildJsColumnsForMTable()}
        		],
        		items: {
        			path: '/data',
                    template: new sap.m.ColumnListItem({
                        type: "Active",
                        cells: [
                            {$this->buildJsCellsForMTable()}
                        ]
                    })
        		}
            })
            .setModel(new sap.ui.model.json.JSONModel());
            {$this->buildJsClickListeners()}
            {$this->buildJsRefresh()};
            return {$this->getJsVar()}
        }()

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
        
        $selection_mode = $widget->getMultiSelect() ? 'sap.ui.table.SelectionMode.MultiToggle' : 'sap.ui.table.SelectionMode.Single';
        $selection_behavior = $widget->getMultiSelect() ? 'sap.ui.table.SelectionBehavior.Row' : 'sap.ui.table.SelectionBehavior.RowOnly';
        
        $js = <<<JS
        function() {
        	{$this->getJsVar()} = new sap.ui.table.Table("{$this->getId()}", {
        		visibleRowCountMode: sap.ui.table.VisibleRowCountMode.Auto
                , selectionMode: {$selection_mode}
        		, selectionBehavior: {$selection_behavior}
        	    , enableColumnReordering:true
                , enableColumnFreeze: true
        		, filter: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(oControlEvent)}
        		, sort: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(oControlEvent)}
        		, toolbar: [
        			{$this->buildJsToolbar()}
        		]
        		, columns: [
        			{$this->buildJsColumnsForUiTable()}
        		]
                , rows: "{/data}"
        	})
            .setModel(new sap.ui.model.json.JSONModel()) 
            /*.addEventDelegate({
                onAfterRendering : function() {
                  console.log('func');
                }
            })*/;
            {$this->getId()}_attachFirstVisibleRowChanged();
            {$this->buildJsClickListeners()}
            {$this->buildJsRefresh()};
            return {$this->getJsVar()}
        }()
JS;
            
        return $js;
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
        $widget_option = $this->getWidget()->getLazyLoading();
        if (is_null($widget_option)) {
            return true;
        }
        return $widget_option;
    }

    /**
     * Returns the definition of a javascript function to fill the table with data: TableIdLoadData(oControlEvent).
     * 
     * The function accepts the following optional JS parameters:
     * - oControlEvent - event that caused the reload (needed for sorting/filtering via column headers to work)
     * 
     * @return string
     */
    protected function buildJsDataSource()
    {
        if (! $this->isLazyLoading()) {
            return $this->buildJsDataSourceOnClient();
        } else {
            return $this->buildJsDataSourceOnServer();
        } 
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataSourceOnClient()
    {
        $widget = $this->getWidget();
        $data = $widget->prepareDataSheetToRead($widget->getValuesDataSheet());
        if (! $data->isFresh()) {
            $data->dataRead();
        }
        
        // FIXME make filtering, sorting, pagination, etc. work in lazy mode too!
        
        return <<<JS
        
    function {$this->buildJsFunctionPrefix()}LoadData(oControlEvent, keep_page_pos) {
        try {
			var data = {$this->getTemplate()->encodeData($this->prepareData($data, false))};
		} catch (err){
            console.error('Cannot load data into widget {$this->getId()}!');
            return;
		}
        sap.ui.getCore().byId("{$this->getId()}").getModel().setData(data);
    }
    
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataSourceOnServer()
    {
        $widget = $this->getWidget();
        
        $url = $this->getAjaxUrl();
        $params = '
					action: "' . $widget->getLazyLoadingActionAlias() . '"
					, resource: "' . $this->getPageId() . '"
					, element: "' . $widget->getId() . '"
					, object: "' . $widget->getMetaObject()->getId() . '"
				';
        
        return <<<JS
        
	function {$this->buildJsFunctionPrefix()}LoadData(oControlEvent, keep_page_pos) {
		var oTable = sap.ui.getCore().byId("{$this->getId()}");
        var params = { {$params} };
		var cols = oTable.getColumns();
		var oModel = oTable.getModel();
		
        oModel.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		
		oModel.attachRequestCompleted(function(oEvent){
			if (oEvent.getParameters().success) {
                {$this->getId()}_pages.total = this.getProperty("/recordsFiltered");
                {$this->getId()}_drawPagination();
                
                if (sap.ui.Device.system.phone) {
                    sap.ui.getCore().byId('{$this->getId()}_page').setHeaderExpanded(false);
                }
                
    			var footerRows = this.getProperty("/footerRows");
                if (footerRows){
    				oTable.setFixedBottomRowCount(parseInt(footerRows));
    			}
            } else {
                var error = oEvent.getParameters().errorobject;
                {$this->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
            }
            
            this.setProperty('/filterDescription', {$this->buildJsFilterSummaryFunctionName()}());
            
            {$this->buildJsBusyIconHide()}
		});
		
		// Add quick search
        params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();
        
        // Add configurator data
        params.data = {$this->getP13nElement()->buildJsDataGetter()};
        
		// Add pagination
        var pages = {$this->getId()}_pages;
        if (! keep_page_pos) {
            pages.resetAll();
        }
        params.start = pages.start;
        params.length = pages.pageSize;

        {$this->buildJsDataSourceColumnActions()}
        
        // Add sorters and filters from P13nDialog
        var aSortItems = sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}').getSortItems();
        console.log(aSortItems.length);
        for (var i in aSortItems) {
            params.sort = (params.sort ? params.sort+',' : '') + aSortItems[i].getColumnKey();
            params.order = (params.order ? params.order+',' : '') + (aSortItems[i].getOperation() == 'Ascending' ? 'asc' : 'desc');
        }
        
        oModel.loadData("{$url}", params);
	}
	
    {$this->buildJsFilterSummaryFunction()}
    
JS;
    }
    
    protected function buildJsDataSourceColumnActions()
    {
        if ($this->isMTable()) {
            return '';
        }
        
        return <<<JS

        // Add filters and sorters from column menus
		for (var i=0; i<oTable.getColumns().length; i++){
			var oColumn = oTable.getColumns()[i];
			if (oColumn.getFiltered()){
				params['fltr99_' + oColumn.getFilterProperty()] = oColumn.getFilterValue();
			}
		}
		
		// If sorting just now, make sure the sorter from the event is set too (eventually overwriting the previous sorting)
		if (oControlEvent && oControlEvent.getId() == 'sort'){
            sap.ui.getCore().byId('{$this->getP13nElement()->getIdOfSortPanel()}')
                .destroySortItems()
                .addSortItem(
                    new sap.m.P13nSortItem({
                        columnKey: oControlEvent.getParameters().column.getSortProperty(),
                        operation: oControlEvent.getParameters().sortOrder
                    })
                );
		}
		
		// If filtering just now, make sure the filter from the event is set too (eventually overwriting the previous one)
		if (oControlEvent && oControlEvent.getId() == 'filter'){
			params['fltr99_' + oControlEvent.getParameters().column.getFilterProperty()] = oControlEvent.getParameters().value;
		}

JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsFilterSummaryFunction()
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
    function {$this->buildJsFilterSummaryFunctionName()}() {
        var filtersCount = 0;
        var filtersList = '';
        {$filter_checks}
        if (filtersCount > 0) {
            return '{$this->translate('WIDGET.DATATABLE.FILTERED_BY')} (' + filtersCount + '): ' + filtersList;
        } else {
            return '{$this->translate('WIDGET.DATATABLE.FILTERED_BY')}: {$this->translate('WIDGET.DATATABLE.FILTERED_BY_NONE')}';
        }
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
     * Returns JavaScript-Functions which are necessary for the pagination.
     *
     * @return string
     */
    protected function buildJsPagination()
    {
        $defaultPageSize = $this->getPaginationPageSize();
        
        return <<<JS

    var {$this->getId()}_pages = {
    	start: 0,
        pageSize: {$defaultPageSize},
        total: 0,
        end: function() {
            return Math.min(this.start + this.pageSize - 1, this.total - 1);
        },
        previous: function() {
            this.resetPageSize();
            if (this.start >= this.pageSize) {
                this.start -= this.pageSize;
            } else {
                this.start = 0;
            }
        },
        next: function() {
            if (this.start < this.total - this.pageSize) {
                this.start += this.pageSize;
            }
            this.resetPageSize();
        },
        increasePageSize: function() {
            this.pageSize += {$defaultPageSize};
        },
        resetPageSize: function() {
            this.pageSize = {$defaultPageSize};
        },
        resetAll: function() {
            this.start = 0;
            this.pageSize = {$defaultPageSize};
            this.total = 0;
        }
    };

    function {$this->getId()}_drawPagination() {
        var pages = {$this->getId()}_pages;
    	if (pages.start === 0) {
            sap.ui.getCore().byId("{$this->getId()}_prev").setEnabled(false);
    	} else {
            sap.ui.getCore().byId("{$this->getId()}_prev").setEnabled(true);
    	}
    	if (pages.end() === (pages.total - 1)) {
            sap.ui.getCore().byId("{$this->getId()}_next").setEnabled(false);
    	} else {
    		sap.ui.getCore().byId("{$this->getId()}_next").setEnabled(true);
    	}
        sap.ui.getCore().byId("{$this->getId()}_pager").setText((pages.start + 1) + ' - ' + (pages.end() + 1) + ' / ' + pages.total);
	};
    
    function {$this->getId()}_attachFirstVisibleRowChanged() {
        var oTable = {$this->getJsVar()};
        oTable.attachFirstVisibleRowChanged(function() {
            var pages = {$this->getId()}_pages;
            var lastVisibleRow = oTable.getFirstVisibleRow() + oTable.getVisibleRowCount();
            if ((pages.pageSize - lastVisibleRow <= 1) && (pages.end() + 1 !== pages.total)) {
                pages.increasePageSize();
                {$this->buildJsRefresh(true)}
            }
        });
    };
JS;
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
    protected function buildJsToolbar()
    {
        $heading = $this->isWrappedInDynamicPage() ? '' : 'new sap.m.Label({text: "' . $this->buildTextTableHeading() . ': "}),';
        $pager = <<<JS
        new sap.m.Label("{$this->getId()}_pager", {
            text: ""
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_prev", {
            icon: "sap-icon://navigation-left-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "{$this->translate('WIDGET.PAGINATOR.PREVIOUS_PAGE')}",
            enabled: false,
            press: function() {
                {$this->getId()}_pages.previous();
                {$this->buildJsRefresh(true)}
            }
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_next", {
            icon: "sap-icon://navigation-right-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "{$this->translate('WIDGET.PAGINATOR.NEXT_PAGE')}",
			enabled: false,
            press: function() {
                {$this->getId()}_pages.next();
                {$this->buildJsRefresh(true)}
            }
        }),
        
JS;
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$heading}
			        {$pager}
                    new sap.m.ToolbarSpacer(),
                    {$this->buildJsButtonsConstructors()}
					new sap.m.SearchField("{$this->getId()}_quickSearch", {
                        width: "200px",
                        search: function(){ {$this->buildJsRefresh()} },
                        placeholder: "{$this->getQuickSearchPlaceholder(false)}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    new sap.m.OverflowToolbarButton({
                        icon: "sap-icon://drop-down-list",
                        text: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        tooltip: "{$this->translate('WIDGET.DATATABLE.SETTINGS_DIALOG.TITLE')}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "High"}),
                        press: function() {
                			{$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->getJsVar()}.open();
                		}
                    }),
                    new sap.m.HBox({
                        visible: false,
                        items: [
                            {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->getJsVar()}
                        ]
                    })		
				]
			})
JS;
        return $toolbar;
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
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsRefresh()
     */
    public function buildJsRefresh($keep_page_pos = false)
    {
        return "{$this->buildJsFunctionPrefix()}LoadData(undefined, " . ($keep_page_pos ? 'true' : 'false') . ')';
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
     * Returns the number of records to show on one page.
     * 
     * @return number
     */
    protected function getPaginationPageSize()
    {
        return $this->getWidget()->getPaginatePageSize() ? $this->getWidget()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE');
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

        new sap.f.DynamicPage("{$this->getId()}_page", {
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
                                text: {
                                    path: "/filterDescription",
                                }
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
        if (is_null($action)) {
            $rows = "sap.ui.getCore().byId('{$this->getId()}').getModel().getData().data";
        } elseif ($action instanceof iReadData) {
            // If we are reading, than we need the special data from the configurator
            // widget: filters, sorters, etc.
            return $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsDataGetter($action);
        } elseif ($this->isEditable() && $action->implementsInterface('iModifyData')) {
            // TODO
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
        
    protected function buildJsClickListeners()
    {
        $widget = $this->getWidget();
        $js = '';
        $rightclick_script = '';
        		
        // Double click. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $js .= <<<JS

            {$this->getJsVar()}.attachBrowserEvent("dblclick", function(oEvent) {
        		{$this->getTemplate()->getElement($dblclick_button)->buildJsClickFunctionName()}();
            });
JS;
        }
        
        // Right click. Currently only supports one double click action - the first one in the list of buttons
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $rightclick_script = $this->getTemplate()->getElement($rightclick_button)->buildJsClickFunctionName() . '()';
        } else {
            $rightclick_script = $this->buildJsContextMenuTrigger();
        }
        
        if ($rightclick_script) {
            $js .= <<<JS
            
            {$this->getJsVar()}.attachBrowserEvent("contextmenu", function(oEvent) {
                oEvent.preventDefault();
                {$rightclick_script}
        	});

JS;
        }
                
        // Single click. Currently only supports one double click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            if ($this->isUiTable()) {
                $js .= <<<JS
                
            {$this->getJsVar()}.attachBrowserEvent("click", function(oEvent) {
        		{$this->getTemplate()->getElement($leftclick_button)->buildJsClickFunctionName()}();
            });
JS;
            } else {
                $js .= <<<JS
                
            {$this->getJsVar()}.attachItemPress(function(oEvent) {
                console.log('click');
        		{$this->getTemplate()->getElement($leftclick_button)->buildJsClickFunctionName()}();
            });
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
        
        /* @var $btn_element \exface\AdminLteTemplate\lteButton */
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
            $menu_item = <<<JS

                        new sap.ui.unified.MenuItem({
                            icon: "{$btn_element->buildCssIconClass($button->getIcon())}",
                            text: "{$button->getCaption()}",
                            select: {$btn_element->buildJsClickFunctionName()},
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
}
?>