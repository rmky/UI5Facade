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
        return '';
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
        foreach ($widget->getTable()->getColumns() as $idx => $col) {
            $columns .= ($columns ? ",\n" : '') . $this->buildJsPropertiesColumn($col);
            $cells .= ($cells ? ",\n" : '') . $this->buildJsPropertiesCell($col);
            if ($col->getId() === $widget->getValueColumn()->getId()) {
                $value_idx = $idx;
            }
            if ($col->getId() === $widget->getTextColumn()->getId()) {
                $text_idx = $idx;
            }
        }
        
        if (is_null($value_idx)) {
            throw new WidgetLogicError($widget, 'Value column not found for ' . $this->getWidget()->getWidgetType() . ' with id "' . $this->getWidget()->getId() . '"!');
        }
        if (is_null($text_idx)) {
            throw new WidgetLogicError($widget, 'Text column not found for ' . $this->getWidget()->getWidgetType() . ' with id "' . $this->getWidget()->getId() . '"!');
        }
        
        // TODO do not instantiate the model every time, but rathe create it once and load data with every suggest.
        
        return <<<JS
	   new sap.m.Input("{$this->getId()}", {
			{$this->buildJsProperties()},
            type: "Text",
			textFormatMode: "ValueKey",
			showSuggestion: true,
            maxSuggestionWidth: "400px",
            startSuggestion: 0,
            showTableSuggestionValueHelp: false,
            filterSuggests: false,
            showValueHelp: true,
			suggest: function(oEvent) {
                var oInput = sap.ui.getCore().byId("{$this->getId()}");
                var params = { 
                    action: "{$widget->getLazyLoadingAction()}",
                    resource: "{$this->getPageId()}",
                    element: "{$widget->getTable()->getId()}",
                    object: "{$widget->getTable()->getMetaObject()->getId()}",
                    length: "{$widget->getMaxSuggestions()}",
				    start: 0,
                    q: oEvent.getParameter("suggestValue")
                };
        		var oModel = new sap.ui.model.json.JSONModel();
                 
        		oModel.loadData("{$this->getAjaxUrl()}", params);

                var oSuggestionRowTemplate = new sap.m.ColumnListItem({
				   cells: [
				       {$cells}
				   ]
				});
                oInput.setModel(oModel);
                oInput.bindAggregation("suggestionRows", "/data", oSuggestionRowTemplate);
    		},
            suggestionItemSelected: function(oEvent){
                var oItem = oEvent.getParameter("selectedRow");
                if (! oItem) return;
				var aCells = oEvent.getParameter("selectedRow").getCells();
                var oInput = sap.ui.getCore().byId("{$this->getId()}");
                oInput.setValue(aCells[ {$text_idx} ].getText());
                oInput.setSelectedKey(aCells[ {$value_idx} ].getText());
			},
			suggestionColumns: [
				{$columns}
            ],
			{$this->buildJsProperties()}
       })
JS;
    }
       
    protected function buildJsPropertiesCell(DataColumn $col)
    {
        return <<<JS

                        new sap.m.Label({
                            text: "{{$col->getDataColumnName()}}", 
                            tooltip: "{{$col->getDataColumnName()}}"
                        })

JS;
    }
       
    protected function buildJsPropertiesColumn(DataColumn $col)
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
                        
    protected function buildJsPropertyValue()
    {
        $value = $this->getWidget()->getValueWithDefaults();
        return ($value ? ', selectedKey: "' . $this->buildJsTextValue($value) . '", value: "' . $this->getWidget()->getValueText() . '"' : '');
    }
                        
    public function buildJsValueGetter()
    {
        return "sap.ui.getCore().byId('{$this->getId()}').getSelectedKey()";
    }
    
    public function buildJsRefresh()
    {
        return "{$this->buildJsFunctionPrefix()}LoadData({$this->getJsVar()})";
    }
}
?>