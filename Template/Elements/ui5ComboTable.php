<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\ComboTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Exceptions\Widgets\WidgetHasNoUidColumnError;
use exface\Core\Exceptions\Widgets\WidgetLogicError;

/**
 * Generates OpenUI5 selects
 *
 * @method ComboTable getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5ComboTable extends ui5Input
{
    
    function generateJs()
    {
        return <<<JS
    var {$this->getJsVar()};	
JS;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildJsElementConstructor()
     */
    protected function buildJsElementConstructor()
    {
        $widget = $this->getWidget();
        
        $columns = '';
        $cells = '';
        foreach ($widget->getTable()->getColumns() as $col) {
            $columns .= ($columns ? ",\n" : '') . $this->buildJsInitOptionsColumn($col);
            $cells .= ($cells ? ",\n" : '') . $this->buildJsInitOptionsCell($col);
        }
        
        return <<<JS
	   new sap.m.Input("{$this->getId()}", {
			{$this->buildJsInitOptions()},
            type: "Text",
			textFormatMode: "ValueKey",
			showSuggestion: true,
            maxSuggestionWidth: "400px",
            startSuggestion: 0,
            showTableSuggestionValueHelp: false,
            filterSuggests: false,
            showValueHelp: true,
			valueHelpRequest: function(){sap.ui.getCore().byId("{$this->getId()}").fireSuggest()},
			suggest: function(oEvent) {
                var oInput = sap.ui.getCore().byId("{$this->getId()}");
                var params = { 
                    action: "{$widget->getLazyLoadingAction()}",
                    resource: "{$this->getPageId()}",
                    element: "{$widget->getTable()->getId()}",
                    object: "{$widget->getTable()->getMetaObject()->getId()}",
                    length: "{$widget->getTable()->getPaginatePageSize()}",
				    start: 0,
                    q: oEvent.getParameter("suggestValue")
                };
        		var oModel = new sap.ui.model.json.JSONModel();
        		
                /*oModel.attachRequestSent(function(){
        			{$this->buildJsBusyIconShow()}
        		});
        		oModel.attachRequestCompleted(function(){
        			{$this->buildJsBusyIconHide()}
        		});*/
        		                    
        		oModel.loadData("{$this->getAjaxUrl()}", params);

                var oSuggestionRowTemplate = new sap.m.ColumnListItem({
				   cells: [
				       {$cells}
				   ]
				});
                oInput.setModel(oModel);
                oInput.bindAggregation("suggestionRows", "/data", oSuggestionRowTemplate);
    		},
            suggestionRowValidator: function (oColumnListItem) {
    			var aCells = oColumnListItem.getCells();
    
    			return new sap.ui.core.Item({
    				key: aCells[ {$this->getCellIndexValue()} ].getText(),
    				text: aCells[ {$this->getCellIndexText()} ].getText()
    			});
    		},
			/*suggestionItemSelected: function(evt) {
                var oInput = sap.ui.getCore().byId('{$this->getId()}'),
    				oText = sap.ui.getCore().byId('selectedKey'),
    				sKey = oInput.getSelectedKey();
    
    			oText.setText(sKey);
            },*/
			suggestionColumns: [
				{$columns}
            ],
			{$this->buildJsInitOptions()}
       })
JS;
    }
       
    protected function buildJsInitOptionsCell(DataColumn $col)
    {
        return <<<JS

                        new sap.m.Label({
                            text: "{{$col->getDataColumnName()}}", 
                            tooltip: "{{$col->getDataColumnName()}}"
                        })

JS;
    }
       
    protected function buildJsInitOptionsColumn(DataColumn $col)
    {
        $options = '';
        
        if ($col->isHidden()){
            $options .= ', visible: false'; 
        }
        
        return <<<JS

                    new sap.m.Column({
						hAlign: "Begin",
						popinDisplay: "Inline",
						demandPopin: true,
						header: [
                            new sap.m.Label({
                                text: "{$col->getCaption()}"
                            })
                        ]
                        {$options}
					})

JS;
    }
                        
    public function buildJsValueGetter()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').getSelectedKey()";
    }
			
    protected function getModelName()
    {
        return 'model_' . $this->getId();
    }
    
    protected function getCellIndexValue()
    {
        $widget = $this->getWidget();
        $idx = array_search($widget->getValueColumn(), $widget->getTable()->getColumns());
        if ($idx === false){
            throw new WidgetLogicError($widget, 'Value column not found for ' . $this->getWidget()->getWidgetType() . ' with id "' . $this->getWidget()->getId() . '"!');
        }
        return $idx;
    }
    
    protected function getCellIndexText()
    {
        $widget = $this->getWidget();
        $idx = array_search($widget->getTextColumn(), $widget->getTable()->getColumns());
        if ($idx === false){
            throw new WidgetLogicError($widget, 'Text column not found for ' . $this->getWidget()->getWidgetType() . ' with id "' . $this->getWidget()->getId() . '"!');
        }
        return $idx;
    }
    
    public function buildJsRefresh()
    {
        return "{$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()})";
    }
}
?>