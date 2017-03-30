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
class ui5BasicElement extends ui5AbstractElement {
	function generate_html(){
		return '';
	}
	
	function generate_js(){
		$widget = $this->get_widget();
		
		// Columns
		foreach ($widget->get_columns() as $column){
			$columns_js .= $this->build_js_column_def($column);
		}
		
		$js = <<<JS
		
	var oTable = new sap.ui.table.Table({
    visibleRowCount: {$widget->get_paginate_default_page_size()},
    selectionMode: sap.ui.table.SelectionMode.Single, //Use Singe or Multi
    navigationMode: sap.ui.table.NavigationMode.Paginator, //Paginator or Scrollbar
    enableColumnReordering:true,       // Allows you to drag and drop the column and reorder the position of the column
  });

	{$columns_js}

	// Now, create a model to bind the table rows.
     //Create a model and bind the table rows to this model
     var oModel = new sap.ui.model.json.JSONModel();        // created a JSON model      

     {$this->build_js_data_source()}

     oTable.setModel(oModel);                                                                                  

     oTable.bindRows("/data");                              // binding all the rows into the model

     //Initially sort the table

     oTable.sort(oTable.getColumns()[0]);   
		
JS;
		return $js;
	}
	
	protected function build_js_data_source($js_filters = ''){
		$url = $this->get_ajax_url() . '&action=' . $this->get_widget()->get_lazy_loading_action() . '&resource=' . $this->get_page_id() . '&element=' . $this->get_widget()->get_id() . '&object=' . $this->get_widget()->get_meta_object()->get_id();
		
		/*return <<<JS
		oModel.setData({modelData: vData});
JS;*/
		return <<<JS
		oModel.loadData("{$url}");
JS;
	}
		
	protected function build_js_column_def(DataColumn $column){
		return <<<JS

 oTable.addColumn(new sap.ui.table.Column({

    label: new sap.ui.commons.Label({text: "{$column->get_caption()}"}),
    template: new sap.ui.commons.TextField().bindProperty("value", "{$column->get_data_column_name()}"),
    sortProperty: "{$column->get_data_column_name()}",
    filterProperty: "{$column->get_data_column_name()}"
}));

JS;
	}
}
?>