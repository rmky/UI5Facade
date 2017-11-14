<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;

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
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    function generateJs()
    {
        return <<<JS
    var {$this->getJsVar()};
	{$this->buildJsDataSource()}  
    {$this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->generateJs()}
		
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::generateJsConstructor()
     */
    public function generateJsConstructor()
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
	{$this->getJsVar()} = new sap.ui.table.Table({
		visibleRowCountMode: sap.ui.table.VisibleRowCountMode.Auto
	    , selectionMode: {$selection_mode}
		, selectionBehavior: {$selection_behavior}
	    , enableColumnReordering:true
		, filter: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(this, oControlEvent)}
		, sort: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(this, oControlEvent)}
		, toolbar: [
			{$this->buildJsToolbar()}
		]
		, columns: [
			{$column_defs}
		]
	});
    
    {$this->buildJsRefresh()};
    return {$this->getJsVar()};
}()
JS;
    
        if ($this->isWrappedInDynamicPage()){
            return $this->buildJsPage($js);
        } else {
            return $js;
        }
    }

    protected function buildJsDataSource()
    {
        $widget = $this->getWidget();
        $url = $this->getAjaxUrl();
        $params = '
					action: "' . $widget->getLazyLoadingAction() . '"
					, resource: "' . $this->getPageId() . '"
					, element: "' . $widget->getId() . '"
					, object: "' . $widget->getMetaObject()->getId() . '"
				';
        
        // Pagination
        $params .= '
					, length: "' . $this->getPaginationPageSize() . '"
					, start: 0
				';
        
        if ($this->isWrappedInDynamicPage()) {
            $dataParam = <<<JS
        params.data = {$this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter()};
JS;
        } else {
            $dataParam = '';
        }
        
        return <<<JS
	
	function {$this->buildJsFunctionPrefix()}LoadData(oTable, oControlEvent) {
		var params = { {$params} };
		var cols = oTable.getColumns();
		var oModel = new sap.ui.model.json.JSONModel();
		
        oModel.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		oModel.attachRequestCompleted(function(){
			{$this->buildJsBusyIconHide()}
			
			var footerRows = this.getProperty("/footerRows");
			if (footerRows){
				oTable.setFixedBottomRowCount(parseInt(footerRows));
			}
		});

		oTable.setModel(oModel); 

        // Add quick search
        params.q = sap.ui.getCore().byId('{$this->getId()}_quickSearch').getValue();

        // Add configurator data
        {$dataParam}
        
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
			console.log(oControlEvent.getParameters().column.getFilterProperty());
			params['fltr99_' + oControlEvent.getParameters().column.getFilterProperty()] = oControlEvent.getParameters().value;
		}
		
		oModel.loadData("{$url}", params);
		oTable.bindRows("/data");
	}

JS;
    }

    protected function buildJsToolbar()
    {
        $heading = $this->isWrappedInDynamicPage() ? '' : 'new sap.m.Label({text: "' . $this->buildTextTableHeading() . '"}),';
        $buttons = $this->buildJsButtons() . ($this->buildJsButtons() ? ',' : '');
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
                design: "Transparent",
				content: [
					{$heading}
                    new sap.m.ToolbarSpacer(),
                    {$buttons}
					new sap.m.ToolbarSeparator(),
					new sap.m.SearchField("{$this->getId()}_quickSearch", {
                        width: "200px",
                        search: function(){ {$this->buildJsRefresh()} },
                        placeholder: "{$this->getQuickSearchPlaceholder()}",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    new sap.m.Button({
                        icon: "sap-icon://group-2",
                        layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
                    }),
                    new sap.m.ToolbarSeparator()		
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
        return "{$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()})";
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
                    $buttons .= ($buttons ? ", \n" : '') . $this->getTemplate()->getElement($btn)->generateJsConstructor();
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
	    label: new sap.ui.commons.Label({text: "{$column->getCaption()}"})
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
        $filters = '';
        foreach ($this->getWidget()->getConfiguratorWidget()->getFilters() as $filter) {
            $filters .= ($filters ? ",\n" : '') . $this->getTemplate()->getElement($filter)->generateJsConstructor();
        }
        
        return <<<JS

        new sap.f.DynamicPage("{$this->getId()}_page",{
            preserveHeaderStateOnScroll: true,
            headerExpanded: "{/headerExpanded}",
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
        return $this->getWidget()->getHideHeader() ? false : true;
    }
}
?>