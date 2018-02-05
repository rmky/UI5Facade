<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Actions\iReadData;

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
        if ($this->getWidget()->isResponsive()) {
            
        } else {
            $js = $this->buildJsConstructorForUiTable();
        }
        
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js);
        } else {
            return $js;
        }
    }
    
    /**
     * Returns the javascript constructor for a sap.m.Table
     * 
     * @return string
     */
    protected function buildJsConstructorForMTable()
    {
        return <<<JS

    new sap.m.Table("{$this->getId()}", {
		headerToolbar: [
            {$this->buildJsToolbar()}
		],
		columns: [
            
		]
		items="{
			path: '/ProductCollection',
			sorter: {
				path: 'Name'
			}
		}">
		<items>
			<ColumnListItem vAlign="Middle" type="Navigation">
				<cells>
					<Text text="{Name}" wrapping="false" />
					<Text text="{SupplierName}" wrapping="false"/>
					<Text text="{Description}" />
				</cells>
			</ColumnListItem>
		</items>
	</Table>

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
        	})/*.addEventDelegate({
                onAfterRendering : function() {
                  console.log('func');
                }
            })*/;
            {$this->getId()}_attachFirstVisibleRowChanged();
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
    
    /**
     * Returns a comma-separated list of column constructors for sap.m.Table
     * 
     * @return string
     */
    protected function buildJsColumnsForMTable()
    {
        // Columns
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
        $widget = $this->getWidget();
        
        if (! $this->isLazyLoading()) {
            return <<<JS

    function {$this->buildJsFunctionPrefix()}LoadData(oControlEvent) {
        
    }

JS;
        }
        
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
		var oModel = new sap.ui.model.json.JSONModel();
		
        oModel.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		oModel.attachRequestCompleted(function(oEvent){
			if (oEvent.getParameters().success) {
                {$this->getId()}_pages.total = this.getProperty("/recordsFiltered");
                {$this->getId()}_drawPagination();
                
                
    			var footerRows = this.getProperty("/footerRows");
                if (footerRows){
    				oTable.setFixedBottomRowCount(parseInt(footerRows));
    			}
            } else {
                var error = oEvent.getParameters().errorobject;
                {$this->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
            }
            {$this->buildJsBusyIconHide()}
		});

		oTable.setModel(oModel); 

        // Add quick search
        params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();

        // Add configurator data
        params.data = {$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter()};
        
		// Add pagination
        var pages = {$this->getId()}_pages;
        if (! keep_page_pos) {
            pages.resetAll();
        }
        params.start = pages.start;
        params.length = pages.pageSize;

        // Add filters and sorters from column menus
		for (var i=0; i<oTable.getColumns().length; i++){
			var oColumn = oTable.getColumns()[i];
			if (oColumn.getFiltered()){
				params['fltr99_' + oColumn.getFilterProperty()] = oColumn.getFilterValue();
			}
			if (oColumn.getSorted()){
				params.sort = oColumn.getSortProperty();
				params.order = (oColumn.getSortOrder() == 'Ascending' ? 'asc' : 'desc');
			}
		}
		
		// If sorting just now, make sure the sorter from the event is set too (eventually overwriting the previous sorting)
		if (oControlEvent && oControlEvent.getId() == 'sort'){
			params.sort = oControlEvent.getParameters().column.getSortProperty();
			params.order = (oControlEvent.getParameters().sortOrder == 'Ascending' ? 'asc' : 'desc');
		}
		
		// If filtering just now, make sure the filter from the event is set too (eventually overwriting the previous one)
		if (oControlEvent && oControlEvent.getId() == 'filter'){
			params['fltr99_' + oControlEvent.getParameters().column.getFilterProperty()] = oControlEvent.getParameters().value;
		}
		
		oModel.loadData("{$url}", params);
		oTable.bindRows("/data");
	}

JS;
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
            if ((oTable.getFirstVisibleRow() + oTable.getVisibleRowCount() === pages.pageSize) &&
                    (pages.end() + 1 !== pages.total)) {
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
            text: "Previous page",
            press: function() {
                {$this->getId()}_pages.previous();
                {$this->buildJsRefresh(true)}
            }
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_next", {
            icon: "sap-icon://navigation-right-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "Next page",
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
				heading: [
						new sap.m.Title({
                            text: "{$this->buildTextTableHeading()}"
                        })
				],
				actions: [
				    new sap.m.ToolbarSpacer(),
                    {$top_buttons}
					new sap.m.OverflowToolbarButton({
						press: function(oEvent){
                            var oPage = sap.ui.getCore().byId('{$this->getId()}_page');
                            var oButton = oEvent.getSource();
                            if (oPage.getHeaderExpanded()) {
                                oPage.setHeaderExpanded(false);
                                oButton
                                    .setIcon('sap-icon://expand-group')
                                    .setText('{$this->translate('WIDGET.DATATABLE.EXPAND_HEADER')}')
                                    .setTooltip('{$this->translate('WIDGET.DATATABLE.EXPAND_HEADER')}');
                            } else {
                                oPage.setHeaderExpanded(true);
                                oButton
                                    .setIcon('sap-icon://collapse-group')
                                    .setText('{$this->translate('WIDGET.DATATABLE.COLLAPSE_HEADER')}')
                                    .setTooltip('{$this->translate('WIDGET.DATATABLE.COLLAPSE_HEADER')}');
                            }
                        },
                        icon: "sap-icon://collapse-group",
                        type: "Transparent",
						text: "{$this->translate('WIDGET.DATATABLE.COLLAPSE_HEADER')}",
                        tooltip: "{$this->translate('WIDGET.DATATABLE.COLLAPSE_HEADER')}"
                    })
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
            $rows = "sap.ui.getCore().byId('{$this->getId()}').getModel().getData().data[sap.ui.getCore().byId('{$this->getId()}').getSelectedIndex()]";
        }
        return <<<JS
    function() {
        var rows = {$rows};
        return {
            oId: '{$this->getWidget()->getMetaObject()->getId()}', 
            rows: (rows === undefined ? [] : [rows])
        };
    }()
JS;
    }
}
?>