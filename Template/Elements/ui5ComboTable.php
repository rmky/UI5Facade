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
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl()
    {
        $widget = $this->getWidget();
        
        if ($value = $widget->getValueWithDefaults()) {
            $value_init_js = '.setValue("' . $this->getWidget()->getValueText() . '").setSelectedKey("' . $this->escapeJsTextValue($value) . '")';
        } else {
            $value_init_js = '';
        }
        
        $columns = '';
        $cells = '';
        foreach ($widget->getTable()->getColumns() as $idx => $col) {
            /* @var $element \exface\OpenUI5Template\Template\Elements\ui5DataColumn */
            $element = $this->getTemplate()->getElement($col);
            $columns .= ($columns ? ",\n" : '') . $element->buildJsConstructorForMColumn();
            $cells .= ($cells ? ",\n" : '') . $element->buildJsConstructorForCell();
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
			{$this->buildJsProperties()}
            type: "{$this->buildJsPropertyType()}",
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
                    action: "{$widget->getLazyLoadingActionAlias()}",
                    resource: "{$this->getPageId()}",
                    element: "{$widget->getTable()->getId()}",
                    object: "{$widget->getTable()->getMetaObject()->getId()}",
                    length: "{$widget->getMaxSuggestions()}",
				    start: 0,
                    q: oEvent.getParameter("suggestValue")
                };
        		
                var oModel = oInput.getModel();
        		oModel.loadData("{$this->getAjaxUrl()}", params);
    		},
            suggestionRows: {
                path: "/data",
                template: new sap.m.ColumnListItem({
				   cells: [
				       {$cells}
				   ]
				})
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
        }).setModel(function(){
            var oModel = new sap.ui.model.json.JSONModel();
            return oModel;
        }()){$value_init_js}{$this->buildJsPseudoEventHandlers()}
JS;
    }
    
    /**
     * The value and selectedKey properties of input controls do not seem to work before
     * a model is bound, so we set initial value programmatically at the end of the constructor.
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5Input::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        return '';
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