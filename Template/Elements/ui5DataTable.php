<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\DataTable;

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
		$js = <<<JS
		
	var vData=[
           {assID:"EM123456", name:"Bharath S", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Male", mobile:"9934307162", rating:5},
           {assID:"EM263521", name:"Arun M", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Male", mobile:"9786721460", rating:3},
           {assID:"EM323455", name:"Anitha", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Female", mobile:"9524396759", rating:4},
           {assID:"EM237652", name:"Ganesh", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Male", mobile:"9876543210", rating:1},
           {assID:"EM398454", name:"Ajai", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Male", mobile:"9576113218", rating:4},
           {assID:"EM348092", name:"Pranav", linkText:"Cognizant Technology Solutions", href:"http://www.cognizant.com", gender:"Male", mobile:"9576113218", rating:5}
          ];
		
	var oTable = new sap.ui.table.Table({
    visibleRowCount: 20,                                           // How much rows you want to display in the table
    selectionMode: sap.ui.table.SelectionMode.Single, //Use Singe or Multi
    navigationMode: sap.ui.table.NavigationMode.Paginator, //Paginator or Scrollbar
    fixedColumnCount: 3,                     // Freezes the number of columns
    enableColumnReordering:true,       // Allows you to drag and drop the column and reorder the position of the column
    width:"1024px"                              // width of the table
  });
// Use the Object defined for table to add new column into the table
    oTable.addColumn(new sap.ui.table.Column({

    label: new sap.ui.commons.Label({text: "Associate ID"}),             // Creates an Header with value defined for the text attribute
    template: new sap.ui.commons.TextField().bindProperty("value", "assID"), // binds the value into the text field defined using JSON
    sortProperty: "assID",        // enables sorting on the column
    filterProperty: "assID",       // enables set filter on the column
    width: "125px"                  // width of the column
}));
    oTable.addColumn(new sap.ui.table.Column({

    label: new sap.ui.commons.Label({text: "Associate Name"}),
    template: new sap.ui.commons.TextField().bindProperty("value", "name"),
    sortProperty: "name",
    filterProperty: "name",
    width: "125px"
}));
    oTable.addColumn(new sap.ui.table.Column({
    label: new sap.ui.commons.Label({text: "Company"}),
    template: new sap.ui.commons.Link().bindProperty("text", "linkText").bindProperty("href", "href"),
    sortProperty: "linkText",
    filterProperty: "linkText",
    width: "200px"
}));
     oTable.addColumn(new sap.ui.table.Column({

     label: new sap.ui.commons.Label({text: "Gender"}),
     template: new sap.ui.commons.ComboBox(
                    {items: [new sap.ui.core.ListItem({text: "Female"}),
                    new sap.ui.core.ListItem({text: "Male"})]}).bindProperty("value","gender"),
     sortProperty: "gender",
     filterProperty: "gender",
     width: "75px"
    }));
    oTable.addColumn(new sap.ui.table.Column({
    label: new sap.ui.commons.Label({text: "Contact Number"}),
    template: new sap.ui.commons.TextField().bindProperty("value", "mobile"),
    sortProperty: "mobile",
    filterProperty: "mobile",
    width: "75px"
}));
    oTable.addColumn(new sap.ui.table.Column({

    label: new sap.ui.commons.Label({text: "Rating"}),
    template: new sap.ui.commons.RatingIndicator().bindProperty("value", "rating"),
    sortProperty: "rating",
    filterProperty: "rating",
    width: "100px"
}));



	// Now, create a model to bind the table rows.
     //Create a model and bind the table rows to this model
     var oModel = new sap.ui.model.json.JSONModel();        // created a JSON model      

     {$this->build_js_data_source()}

     oTable.setModel(oModel);                                                                                  

     oTable.bindRows("/modelData");                              // binding all the rows into the model

     //Initially sort the table

     oTable.sort(oTable.getColumns()[0]);   
		
JS;
		return $js;
	}
	
	protected function build_js_data_source($js_filters = ''){
		$url = $this->get_ajax_url() . '&action=' . $this->get_widget()->get_lazy_loading_action() . '&resource=' . $this->get_page_id() . '&element=' . $this->get_widget()->get_id() . '&object=' . $this->get_widget()->get_meta_object()->get_id();
		
		return <<<JS
		oModel.setData({modelData: vData});
JS;
	}
}
?>