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

    function generateHtml()
    {
        return '';
    }

    function generateJs()
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
	});   

	{$this->buildJsDataSource()}  
		
JS;
        return $js;
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
		var {$this->getJsVarModel()} = new sap.ui.model.json.JSONModel();
		
		{$this->getJsVarModel()}.attachRequestSent(function(){
			{$this->buildJsBusyIconShow()}
		});
		{$this->getJsVarModel()}.attachRequestCompleted(function(){
			{$this->buildJsBusyIconHide()}
			
			var footerRows = this.getProperty("/footerRows");
			if (footerRows){
				{$this->getJsVar()}.setFixedBottomRowCount(parseInt(footerRows));
			}
		});

		{$this->getJsVar()}.setModel({$this->getJsVarModel()}); 

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
		
		{$this->getJsVarModel()}.loadData("{$url}", params);
		{$this->getJsVar()}.bindRows("/data");
	}
	
	
	{$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()});
		
JS;
    }
	
    protected function getJsVarModel()
    {
        return $this->getJsVar().'Model';
    }

    protected function buildJsToolbar()
    {
        $header = $this->getWidget()->getCaption() ? $this->getWidget()->getCaption() : $this->getWidget()->getMetaObject()->getName();
        $toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
					new sap.m.Label({text: "{$header}"})
				]
			})
JS;
        return $toolbar;
    }

    protected function getPaginationPageSize()
    {
        return $this->getWidget()->getPaginatePageSize() ? $this->getWidget()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE');
    }

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