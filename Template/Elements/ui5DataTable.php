<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Widgets\DataColumn;

/**
 * 
 * @method DataTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5DataTable extends ui5AbstractElement {
	function generate_html(){
		return '';
	}
	
	function generate_js(){
		$widget = $this->get_widget();
		
		$selection_mode = $widget->get_multi_select() ? 'sap.ui.table.SelectionMode.MultiToggle' : 'sap.ui.table.SelectionMode.Single';
		$selection_behavior = $widget->get_multi_select() ? 'sap.ui.table.SelectionBehavior.Row' : 'sap.ui.table.SelectionBehavior.RowOnly';
		
		// Columns
		$column_defs = '';
		foreach ($widget->get_columns() as $column){
			$column_defs .= ($column_defs ? ", " : '') . $this->build_js_column_def($column);
		}
		
		$js = <<<JS
		
	var oTable = new sap.ui.table.Table({
		visibleRowCountMode: "Auto"
	    , selectionMode: {$selection_mode}
		, selectionBehavior: {$selection_behavior}
	    , enableColumnReordering:true
		, filter: function(oControlEvent){{$this->build_js_function_prefix()}LoadData(this, oControlEvent)}
		, sort: function(oControlEvent){{$this->build_js_function_prefix()}LoadData(this, oControlEvent)}
		, extension: [
			{$this->build_js_toolbar()}
		]
		, columns: [
			{$column_defs}
		]
	});   

	{$this->build_js_data_source()}  
		
JS;
		return $js;
	}
	
	protected function build_js_data_source($js_filters = ''){
		$url = $this->get_ajax_url();
		$params = '
					action: "' . $this->get_widget()->get_lazy_loading_action() . '"
					, resource: "' . $this->get_page_id() . '"
					, element: "' . $this->get_widget()->get_id() . '"
					, object: "' . $this->get_widget()->get_meta_object()->get_id() . '"
				';
		
		// Pagination
		$params .= '
					, length: "' . $this->get_widget()->get_paginate_default_page_size() . '"
					, start: 0
				';
		
		return <<<JS
	
	function {$this->build_js_function_prefix()}LoadData(oTable, oControlEvent) {
		var params = { {$params} };
		var cols = oTable.getColumns();
		var oModel = new sap.ui.model.json.JSONModel();
		
		oModel.attachRequestSent(function(){
			{$this->build_js_busy_icon_show()}
		});
		oModel.attachRequestCompleted(function(){
			{$this->build_js_busy_icon_hide()}
			var footerRows = this.getProperty("/footerRows");
			if (footerRows){
				oTable.setFixedBottomRowCount(parseInt(footerRows));
			}
		});

		oTable.setModel(oModel); 
		
		if (oControlEvent && oControlEvent.getId() == 'sort'){
			params.sort = 	oControlEvent.getParameters().column.getSortProperty();
			params.order = 	(oControlEvent.getParameters().sortOrder == 'Ascending' ? 'asc' : 'desc');
		}
		
		if (oControlEvent && oControlEvent.getId() == 'filter'){
			params['fltr99_' + oControlEvent.getParameters().column.getFilterProperty()] = oControlEvent.getParameters().value;
		}
		
		oModel.loadData("{$url}", params);
		oTable.bindRows("/data");
	}
	
	
	{$this->build_js_function_prefix()}LoadData(oTable);
		
JS;
	}
	
	protected function build_js_toolbar(){
		$header = $this->get_widget()->get_caption() ? $this->get_widget()->get_caption() : $this->get_widget()->get_meta_object()->get_name();
		$toolbar = <<<JS
			new sap.m.OverflowToolbar({
				content: [
					new sap.m.Label({text: "{$header}"})
				]
			})
JS;
		return $toolbar;
	}
		
	protected function build_js_column_def(DataColumn $column){
		$visible = $column->is_hidden() ? 'false' : 'true';
		$textAlign = 'sap.ui.core.TextAlign.' . ucfirst($column->get_align());
		
		return <<<JS
	 new sap.ui.table.Column({
	    label: new sap.ui.commons.Label({text: "{$column->get_caption()}"})
	    , template: new sap.ui.commons.TextField({textAlign: {$textAlign}}).bindProperty("value", "{$column->get_data_column_name()}")
	    , sortProperty: "{$column->get_attribute_alias()}"
	    , filterProperty: "{$column->get_attribute_alias()}"
		, visible: {$visible}
	})
JS;
	}
}
?>