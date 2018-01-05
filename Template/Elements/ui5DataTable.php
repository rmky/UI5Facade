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
        $widget = $this->getWidget();
        
        $selection_mode = $widget->getMultiSelect() ? 'sap.ui.table.SelectionMode.MultiToggle' : 'sap.ui.table.SelectionMode.Single';
        $selection_behavior = $widget->getMultiSelect() ? 'sap.ui.table.SelectionBehavior.Row' : 'sap.ui.table.SelectionBehavior.RowOnly';
        
        // Columns
        $column_defs = '';
        foreach ($widget->getColumns() as $column) {
            $column_defs .= ($column_defs ? ", " : '') . $this->buildJsColumnDef($column);
        }
        
        $js = <<<JS
        function() {
        	{$this->getJsVar()} = new sap.ui.table.Table("{$this->getId()}", {
        		visibleRowCountMode: sap.ui.table.VisibleRowCountMode.Auto
                , selectionMode: {$selection_mode}
        		, selectionBehavior: {$selection_behavior}
        	    , enableColumnReordering:true
        		, filter: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(oControlEvent)}
        		, sort: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(oControlEvent)}
        		, toolbar: [
        			{$this->buildJsToolbar()}
        		]
        		, columns: [
        			{$column_defs}
        		]
        	})/*.addEventDelegate({
                onAfterRendering : function() {
                  console.log('func');
                }
            })*/;
            {$this->buildJsRefresh()};
            return {$this->getJsVar()}
        }()
JS;
    
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js);
        } else {
            return $js;
        }
    }
    
    /**
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
        
        // Pagination
        $params .= '
					, length: "' . $this->getPaginationPageSize() . '"
					, start: 0
				';        
        
        return <<<JS
	
	function {$this->buildJsFunctionPrefix()}LoadData(oControlEvent) {
		var oTable = sap.ui.getCore().byId("{$this->getId()}");
        var params = { {$params} };
		var cols = oTable.getColumns();
		var oModel = new sap.ui.model.json.JSONModel();
		
        oModel.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		oModel.attachRequestCompleted(function(oEvent){
			{$this->buildJsBusyIconHide()}
		    if (oEvent.getParameters().success) {
                var total = this.getProperty("/recordsFiltered");
                var start = this.getProperty("/recordsOffset");
                var end = Math.min(start + this.getProperty("/recordsLimit"), total);
                sap.ui.getCore().byId("{$this->getId()}_pager").setText(start + ' - ' + end + ' / ' + total);
                
    			var footerRows = this.getProperty("/footerRows");
                if (footerRows){
    				oTable.setFixedBottomRowCount(parseInt(footerRows));
    			}
            } else {
                var error = oEvent.getParameters().errorobject;
                {$this->buildJsShowError('error.responseText', "(error.statusCode+' '+error.statusText)")}
            }
		});

		oTable.setModel(oModel); 

        // Add quick search
        params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();

        // Add configurator data
        params.data = {$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter()};
        
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
            enabled: false
        }),
        new sap.m.OverflowToolbarButton("{$this->getId()}_next", {
            icon: "sap-icon://navigation-right-arrow",
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "Low"}),
            text: "Next page",
        }),
        
JS;
        $buttons = $this->buildJsButtons() . ($this->buildJsButtons() ? ',' : '');
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$heading}
			        {$pager}
                    new sap.m.ToolbarSpacer(),
                    {$buttons}
					new sap.m.SearchField("{$this->getId()}_quickSearch", {
                        width: "200px",
                        search: function(){ {$this->buildJsRefresh()} },
                        placeholder: "{$this->getQuickSearchPlaceholder(false)}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    new sap.m.Button({
                        icon: "sap-icon://action-settings",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"}),
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
    
    protected function buildTextTableHeading()
    {
        $widget = $this->getWidget();
        return $widget->getCaption() ? $widget->getCaption() : $widget->getMetaObject()->getName();
    }
    
    public function buildJsRefresh()
    {
        return "{$this->buildJsFunctionPrefix()}LoadData()";
    }
                    
    protected function buildJsButtons()
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
                    $buttons .= ($buttons ? ", \n" : '') . $this->getTemplate()->getElement($btn)->buildJsConstructor();
                }
            }
        }
        return $buttons;
    }

    protected function getPaginationPageSize()
    {
        return $this->getWidget()->getPaginatePageSize() ? $this->getWidget()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE');
    }

    /**
     * Returns the constructor for a sap.ui.table.Column created from the given DataColumn widget
     * 
     * @param DataColumn $column
     * @return string
     */
    protected function buildJsColumnDef(DataColumn $column)
    {
        $visible = $column->isHidden() ? 'false' : 'true';
        switch ($column->getAlign()) {
            case EXF_ALIGN_RIGHT:
            case EXF_ALIGN_OPPOSITE:
                $alignment = 'textAlign: sap.ui.core.TextAlign.End';
                break;
            case EXF_ALIGN_CENTER:
                $alignment = 'textAlign: sap.ui.core.TextAlign.Center';
                break;
            case EXF_ALIGN_LEFT:
            case EXF_ALIGN_DEFAULT:
            default:
                $alignment = 'textAlign: sap.ui.core.TextAlign.Begin';
                                
        }
        
        return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({
            text: "{$column->getCaption()}"
        })
        , tooltip: "{$column->getCaption()}"
	    , template: new sap.ui.commons.TextField({
            {$alignment}
        }).bindProperty("value", "{$column->getDataColumnName()}")
	    , sortProperty: "{$column->getAttributeAlias()}"
	    , filterProperty: "{$column->getAttributeAlias()}"
		, visible: {$visible}
	})
JS;
    }
        
    protected function buildJsPage($content)
    {
        $filters = $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsFilters();
        
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
				    new sap.m.ToolbarSpacer()/*,
					new sap.m.ToggleButton({
						pressed: "{/headerExpanded}",
                        icon: "sap-icon://collapse-group",
                        type: "Transparent",
						text: "{path:'/headerExpanded', formatter:'.formatToggleButtonText'}"
                    })*/
				]
            }),

			header: new sap.f.DynamicPageHeader({
                pinnable: true,
				content: [
                    new sap.ui.layout.Grid({
                        defaultSpan: "XL2 L3 M4 S12",
                        content: [
							{$filters}
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