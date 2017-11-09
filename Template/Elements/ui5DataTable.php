<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;

/**
 *
 * @method DataTable getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class ui5DataTable extends ui5AbstractElement
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJs()
     */
    function generateJs()
    {
        return <<<JS

	{$this->buildJsDataSource()}  
		
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
        
        if ($widget->hasButtons()) {
            $footer = ', footer: [' . $this->buildJsFooter() . ']';
        } else {
            $footer = '';
        }
        
        return <<<JS
function() {
	var {$this->getJsVar()} = new sap.ui.table.Table({
		visibleRowCountMode: sap.ui.table.VisibleRowCountMode.Auto
	    , selectionMode: {$selection_mode}
		, selectionBehavior: {$selection_behavior}
	    , enableColumnReordering:true
		, filter: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(this, oControlEvent)}
		, sort: function(oControlEvent){{$this->buildJsFunctionPrefix()}LoadData(this, oControlEvent)}
		, extension: [
			{$this->buildJsToolbar()}
		]
		, columns: [
			{$column_defs}
		]
        {$footer}
	});
    
    {$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()});
    return {$this->getJsVar()};
}()
JS;
    }

    protected function buildJsDataSource($js_filters = '')
    {
        $url = $this->getAjaxUrl();
        $params = '
					action: "' . $this->getWidget()->getLazyLoadingAction() . '"
					, resource: "' . $this->getPageId() . '"
					, element: "' . $this->getWidget()->getId() . '"
					, object: "' . $this->getWidget()->getMetaObject()->getId() . '"
				';
        
        // Pagination
        $params .= '
					, length: "' . $this->getPaginationPageSize() . '"
					, start: 0
				';
        
        return <<<JS
	
	function {$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()}, oControlEvent) {
		var params = { {$params} };
		var cols = {$this->getJsVar()}.getColumns();
		var oModel = new sap.ui.model.json.JSONModel();
		
		oModel.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		oModel.attachRequestCompleted(function(){
			{$this->buildJsBusyIconHide()}
			
			var footerRows = this.getProperty("/footerRows");
			if (footerRows){
				{$this->getJsVar()}.setFixedBottomRowCount(parseInt(footerRows));
			}
		});

		{$this->getJsVar()}.setModel(oModel); 

		// Add filters and sorters from all filtered columns
		for (var i=0; i<{$this->getJsVar()}.getColumns().length; i++){
			var oColumn = {$this->getJsVar()}.getColumns()[i];
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
		{$this->getJsVar()}.bindRows("/data");
	}

JS;
    }

    protected function buildJsToolbar()
    {
        $widget = $this->getWidget();
        $header = $widget->getCaption() ? $widget->getCaption() : $widget->getMetaObject()->getName();
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
					new sap.m.Label({text: "{$header}"})
				]
			})
JS;
        return $toolbar;
    }
    
    /**
     * Returns the constructor of a Toolbar component to display in the footer of the table: e.g. new  new sap.m.OverflowToolbar(...).
     * 
     * @return string
     */
    protected function buildJsFooter()
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
                $buttons .= ($buttons ? ",\n new sap.m.ToolbarSpacer()" : '');
                foreach ($btn_group->getButtons() as $btn) {
                    $buttons .= ($buttons ? ", \n" : '') . $this->getTemplate()->getElement($btn)->generateJsConstructor();
                }
            }
        }
        
        return <<<JS

            new sap.m.OverflowToolbar({
                content: [
                    {$buttons}
                ]
            })

JS;
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
        $textAlign = 'sap.ui.core.TextAlign.' . ucfirst($column->getAlign());
        
        return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({text: "{$column->getCaption()}"})
	    , template: new sap.ui.commons.TextField({textAlign: {$textAlign}}).bindProperty("value", "{$column->getDataColumnName()}")
	    , sortProperty: "{$column->getAttributeAlias()}"
	    , filterProperty: "{$column->getAttributeAlias()}"
		, visible: {$visible}
	})
JS;
    }
}
?>