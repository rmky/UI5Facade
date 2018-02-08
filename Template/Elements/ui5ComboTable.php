<?php
namespace exface\OpenUI5Template\Template\Elements;

use exface\Core\Widgets\ComboTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Exceptions\Widgets\WidgetHasNoUidColumnError;
use exface\Core\Exceptions\Widgets\WidgetLogicError;
use exface\Core\Factories\DataSheetFactory;

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
            if (is_null($widget->getValueText())) {
                $value_init_js = '.' . $this->buildJsValueSetterMethod('"' . $this->escapeJsTextValue($value) . '"');
            } else {
                $value_init_js = '.setValue("' . $widget->getValueText() . '").setSelectedKey("' . $this->escapeJsTextValue($value) . '")';
            }
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
            {$this->buildJsPropertyType()}
			textFormatMode: "ValueKey",
			showSuggestion: true,
            maxSuggestionWidth: "400px",
            startSuggestion: 0,
            showTableSuggestionValueHelp: false,
            filterSuggests: false,
            showValueHelp: true,
			suggest: {$this->buildJsPropertySuggest()},
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
     * Returns the function to be called for autosuggest.
     * 
     * This makes an AJAX requests to fetch suggestions. Normally the
     * event parameter "suggestValue" will contain the text typed by
     * the user and will be used as the autosuggest query. 
     * 
     * To make the programmatic value setter work, there is also a 
     * possibility to pass an object instead of text when firing the 
     * suggest event automatically (see buildJsDataSetterMethod()).
     * In this case, the properties of that object will be used as 
     * parameters of the AJAX request directly. This also will "silence"
     * the request and make the control refresh it's value automatically
     * if the expected suggestion rows (matching the filter) will be
     * returned. This way, setting just the value (key) will lead to
     * a silent autosuggest and the selection of the correkt text value.
     * 
     * @return string
     */
    protected function buildJsPropertySuggest()
    {
        $widget = $this->getWidget();
        return <<<JS
            function(oEvent) {
                var oInput = sap.ui.getCore().byId("{$this->getId()}");
                var q = oEvent.getParameter("suggestValue");
                var qParams = {};
                var silent = false;

                if (typeof q == 'object') {
                    qParams = q;
                    silent = true;
                } else {
                    qParams.q = q;
                }
                var params = { 
                    action: "{$widget->getLazyLoadingActionAlias()}",
                    resource: "{$this->getPageId()}",
                    element: "{$widget->getTable()->getId()}",
                    object: "{$widget->getTable()->getMetaObject()->getId()}",
                    length: "{$widget->getMaxSuggestions()}",
				    start: 0
                };
                $.extend(params, qParams);
        		
                var oModel = oInput.getModel();
                if (silent) {
                    var silencer = function(oEvent){
                        if (oEvent.getParameters().success) {
                            var data = this.getProperty('/data');
                            var curVal = oInput.getSelectedKey();
                            if (parseInt(this.getProperty("/recordsTotal")) == 1 && data[0]['{$widget->getValueColumn()->getDataColumnName()}'] == curVal) {
                                oInput.setValue(this.getProperty('/data')[0]['{$widget->getTextColumn()->getDataColumnName()}']).setSelectedKey(curVal);
                            } else {
                                oInput.setSelectedKey("");
                            }
                        }
                        this.detachRequestCompleted(silencer);
                    };
                    oModel.attachRequestCompleted(silencer);
                }
                oModel.loadData("{$this->getAjaxUrl()}", params);
    		}
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getSelectedKey()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Template\Elements\ui5AbstractElement::buildJsValueSetter()
     */
    public function buildJsValueSetterMethod($valueJs)
    {
        // After setting the key, we need to fetch the corresponding text value, so we use a trick
        // and pass the given value not directly, but wrapped in an object. The suggest-handler
        // above will recognize this and use merge this object with the request parameters, so
        // we can directly tell it to use our input as a value column filter instead of a regular
        // suggest string.
        return "setSelectedKey({$valueJs}).fireSuggest({suggestValue: {fltr00_" . $this->getWidget()->getValueColumn()->getDataColumnName() . ": {$valueJs}}})";
    }
}
?>