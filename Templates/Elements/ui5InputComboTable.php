<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputComboTable;
use exface\Core\Widgets\DataColumn;
use exface\Core\Exceptions\Widgets\WidgetHasNoUidColumnError;
use exface\Core\Exceptions\Widgets\WidgetLogicError;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\DataTypes\UrlDataType;

/**
 * Generates OpenUI5 selects
 *
 * @method InputComboTable getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5InputComboTable extends ui5Input
{
    
    protected function init()
    {
        // If the combo does not allow new values, we need to force the ui5 input to
        // check any input via autosuggest _before_ any other action is taken.
        // TODO this only works if there was no value before and needs to be
        // extended to work with changing values too.
        if (! $this->getWidget()->getAllowNewValues()) {
            $onChange = <<<JS
    
                        oInput = event.getSource();
                        if (oInput.getValue() !== '' && oInput.getSelectedKey() === ''){
                            oInput.fireSuggest({suggestValue: {q: oInput.getValue()}});
                            event.cancelBubble();
                            event.preventDefault();
                            return false;
                        }
                        if (oInput.getValue() === '' && oInput.getSelectedKey() === ''){
                            oInput.setValueState(sap.ui.core.ValueState.None);
                        }
JS;
            $this->addOnChangeScript($onChange);
            
            /*$onAfterRendering = <<<JS
                        oInput = oEvent.srcControl;
                        console.log(oInput.getValue() !== '' && oInput.getSelectedKey() === '');
                        if (oInput.getValue() !== '' && oInput.getSelectedKey() === ''){
                            oInput.fireSuggest({suggestValue: {q: oInput.getValue()}})
                            oEvent.stopPropagation();
                            oEvent.preventDefault();
                            return false;
                        }
JS;
            $this->addPseudoEventHandler('onAfterRendering', $onAfterRendering);*/
            
            // TODO explicitly prevent propagation of enter-events to stop data widgets
            // from autoreloading if enter was pressed to soon.
            $onEnter = <<<JS
                
                        oInput = oEvent.srcControl;
                        if (oInput.getValue() !== '' && oInput.getSelectedKey() === ''){
                            oEvent.stopPropagation();
                            oEvent.preventDefault();
                            return false;
                        }
JS;
                
            $this->addPseudoEventHandler('onsapenter', $onEnter);
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        $controller = $this->getController();
        
        if ($widget->isPreloadDataEnabled()) {
            $cols = '';
            foreach ($widget->getTable()->getColumns() as $col) {
                $cols .= '"' . $col->getDataColumnName() . '",';
            }
            $cols = rtrim($cols, ",");
            $controller->addOnDefineScript("exfPreloader.addPreload('{$widget->getTableObject()->getAliasWithNamespace()}', [{$cols}], [], '{$widget->getPage()->getAliasWithNamespace()}', '{$widget->getTable()->getId()}');");
        }
        
        $controller->addMethod('onSuggestFromServer', $this, 'oEvent', $this->buildJsDataLoderFromServer('oEvent'));
        $controller->addMethod('onSuggestFromPreload', $this, 'oEvent', $this->buildJsDataLoaderFromPreload('oEvent'));
        
        if (! $this->isValueBoundToModel() && $value = $widget->getValueWithDefaults()) {
            // If the widget value is set explicitly, we either set the key only or the 
            // key and the text (= value of the input)
            if ($widget->getValueText() === null || $widget->getValueText() === '') {
                $value_init_js = <<<JS

        .{$this->buildJsValueSetterMethod('"' . $this->escapeJsTextValue($value) . '"')}
JS;
            } else {
                $value_init_js = <<<JS

        .setValue("{$widget->getValueText()}")
        .setSelectedKey("{$this->escapeJsTextValue($value)}")
JS;
            }
        } else {
            // If the value is to be taken from a model, we need to check if both - key
            // and value are there. If not, the value needs to be fetched from the server.
            // FIXME for some reason sKey is sometimes empty despite the binding getting a value...
            // This seems to happen in non-maximized dialogs (e.g. editor of a small object).
            $value_init_js = <<<JS

        .attachModelContextChange(function(oEvent) {
            var oInput = oEvent.getSource();
            var sKey = sap.ui.getCore().byId('{$this->getId()}').getSelectedKey();
            var sVal = oInput.getValue();
            if (sKey !== '' && sVal === '') {
                oInput.{$this->buildJsValueSetterMethod('sKey')};
            }
        })
JS;
        }
        
        // See if there are promoted columns. If not, make the first two visible columns 
        // promoted to make sap.m.table look nice on mobiles.
        $promotedCols = [];
        $firstVisibleCols = [];
        foreach ($widget->getTable()->getColumns() as $col) {
            if (! $col->isHidden()) {
                if (empty($firstVisibleCols)) {
                    $firstVisibleCols[] = $col;
                } elseif (count($firstVisibleCols) === 1) {
                    $firstVisibleCols[] = $col;
                }
                
                if ($col->getVisibility() === EXF_WIDGET_VISIBILITY_PROMOTED) {
                    $promotedCols[] = $col;
                    break;
                }
            }
            
        }
        if (empty($promotedCols) && ! empty($firstVisibleCols)) {
            // If the first automatically selected column is right-aligned, it will not
            // look nice, so change the order of the columns. Actually, the condition
            // is right the opposite, because the columns will be added to the beginning
            // of the list one after another, so the first column ends up being last.
            // TODO Make column reordering depend on the screen size. On desktops, having
            // right-aligned column in the middle does not look good, but on mobiles it
            // is very important. Maybe generate two sets of columns and assign one of
            // them depending on jQuery.device.is.phone?
            if (! ($firstVisibleCols[0]->getAlign() !== EXF_ALIGN_DEFAULT || $firstVisibleCols[0]->getAlign() === EXF_ALIGN_LEFT)) {
                $firstVisibleCols = array_reverse($firstVisibleCols);
            }
            foreach ($firstVisibleCols as $col) {
                $widget->getTable()->removeColumn($col);
                $col->setVisibility(EXF_WIDGET_VISIBILITY_PROMOTED);
                $widget->getTable()->addColumn($col, 0);
            }
        }
        
        // Now generate columns and cells from the column widgets
        $columns = '';
        $cells = '';
        foreach ($widget->getTable()->getColumns() as $idx => $col) {
            /* @var $element \exface\OpenUI5Template\Templates\Elements\ui5DataColumn */
            $element = $this->getTemplate()->getElement($col);
            $columns .= ($columns ? ",\n" : '') . $element->buildJsConstructorForMColumn();
            $cells .= ($cells ? ",\n" : '') . $element->buildJsConstructorForCell($this->getModelNameForAutosuggest());
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
        
        return <<<JS

	   new sap.m.Input("{$this->getId()}", {
			{$this->buildJsProperties()}
            {$this->buildJsPropertyType()}
			textFormatMode: "ValueKey",
			showSuggestion: true,
            maxSuggestionWidth: "400px",
            startSuggestion: 1,
            showTableSuggestionValueHelp: false,
            filterSuggests: false,
            showValueHelp: true,
			suggest: {$this->buildJsPropertySuggest()},
            suggestionRows: {
                path: "{$this->getModelNameForAutosuggest()}>/data",
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
                oInput.setValueState(sap.ui.core.ValueState.None);
			},
			suggestionColumns: [
				{$columns}
            ],
			{$this->buildJsProperties()}
        })
        .setModel(new sap.ui.model.json.JSONModel(), "{$this->getModelNameForAutosuggest()}")
        {$value_init_js}
        {$this->buildJsPseudoEventHandlers()}
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
        
        if ($widget->isPreloadDataEnabled()) {
            $js = $this->getController()->buildJsMethodCallFromController('onSuggestFromPreload', $this, 'oEvent', 'oController');
        } else {
            $js = $this->getController()->buildJsMethodCallFromController('onSuggestFromServer', $this, 'oEvent', 'oController');
        }
        
        return <<<JS
            function(oEvent) {
                {$js}
    		}
JS;
    }
        
    protected function buildJsDataLoderFromServer(string $oEventJs = 'oEvent') : string
    {
        $widget = $this->getWidget();
        return <<<JS

                var oInput = {$oEventJs}.getSource();
                var q = {$oEventJs}.getParameter("suggestValue");
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
        		
                var oModel = oInput.getModel('{$this->getModelNameForAutosuggest()}');
                if (silent) {
                    {$this->buildJsBusyIconShow()}
                    var silencer = function(oEvent){
                        if (oEvent.getParameters().success) {
                            var data = this.getProperty('/data');
                            var curKey = oInput.getSelectedKey();
                            if (parseInt(this.getProperty("/recordsTotal")) == 1 && (curKey === '' || data[0]['{$widget->getValueColumn()->getDataColumnName()}'] == curKey)) {
                                oInput.setValue(data[0]['{$widget->getTextColumn()->getDataColumnName()}']).setSelectedKey(data[0]['{$widget->getValueColumn()->getDataColumnName()}']);
                                oInput.closeSuggestions();
                                oInput.setValueState(sap.ui.core.ValueState.None);
                            } else {
                                oInput.setSelectedKey("");
                                oInput.setValueState(sap.ui.core.ValueState.Error);
                            }
                        }
                        this.detachRequestCompleted(silencer);
                        {$this->buildJsBusyIconHide()}
                    };
                    oModel.attachRequestCompleted(silencer);
                }
                oModel.loadData("{$this->getAjaxUrl()}", params);

JS;
    }
    
    /**
     * Returns the code for a controller method to load suggestion data from a preload.
     * 
     * If no preload data is there, the server data loader method will be called as fallback. So
     * it is important to keep both methods in the controller.
     * 
     * @param string $oEventJs
     * @return string
     */
    protected function buildJsDataLoaderFromPreload(string $oEventJs = 'oEvent') : string
    {
        $widget = $this->getWidget();
        return <<<JS
                
                var oInput = {$oEventJs}.getSource();
                var q = {$oEventJs}.getParameter("suggestValue");
                var qParams = {};
                var silent = false;

                if (typeof q == 'object') {
                    qParams = q;
                    silent = true;
                } else {
                    qParams.q = q;
                }

                // Copy the event for the server fallback in case there is no preloaded data.
                // Need to use a copy, because otherwise the event is emptied before we
                // actually come to the fallback.
                var oSuggestEvent = jQuery.extend({}, oEvent);
                
                var oModel = oInput.getModel('{$this->getModelNameForAutosuggest()}');
                
                exfPreloader
                .getPreload('{$widget->getTableObject()->getAliasWithNamespace()}', '{$widget->getPage()->getAliasWithNamespace()}', '{$widget->getId()}')
                .then(preload => {
                    if (preload !== undefined && preload.response !== undefined && preload.response.data !== undefined) {
                        var curKey = oInput.getSelectedKey();
                        oModel.setData(preload.response);
                        if (silent && curKey !== undefined && curKey !== '') {
                            var oContext = oInput
                                .getBinding('suggestionRows')
                                .filter([
                                    new sap.ui.model.Filter(
                        				"{$widget->getValueColumn()->getDataColumnName()}",
                        				sap.ui.model.FilterOperator.EQ, 
                                        curKey
                                    )
                                ])
                                .getCurrentContexts()[0];
                            if (oContext !== undefined) {
                                var item = oContext.getProperty();
                                oInput.setValue(item['{$widget->getTextColumn()->getDataColumnName()}']).setSelectedKey(item['{$widget->getValueColumn()->getDataColumnName()}']);
                                oInput.closeSuggestions();
                                oInput.setValueState(sap.ui.core.ValueState.None);
                            } else {
                                oInput.setSelectedKey("");
                                oInput.setValueState(sap.ui.core.ValueState.Error);
                            }
                        } else {
                            oInput.getBinding('suggestionRows').filter([
                                new sap.ui.model.Filter(
                    				"{$widget->getTextColumn()->getDataColumnName()}",
                    				sap.ui.model.FilterOperator.Contains, 
                                    qParams.q
                    			)
                            ]);
                        }  
                    } else {
                        {$this->getController()->buildJsMethodCallFromController('onSuggestFromServer', $this, 'oSuggestEvent', 'this')}
                    }
                });

JS;
    }
    
    /**
     * The value and selectedKey properties of input controls do not seem to work before
     * a model is bound, so we set initial value programmatically at the end of the constructor.
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        $widget = $this->getWidget();
        $model = $this->getView()->getModel();
        if ($model->hasBinding($widget, 'value_text')) {
            $valueBinding = ' value: "{' . $model->getBindingPath($widget, 'value_text') . '}",';
        }
        if ($this->isValueBoundToModel()) {
            return 'selectedKey: ' . $this->buildJsValueBinding() . ',' . $valueBinding;
        }
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getSelectedKey()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsValueSetter()
     */
    public function buildJsValueSetterMethod($valueJs)
    {
        // After setting the key, we need to fetch the corresponding text value, so we use a trick
        // and pass the given value not directly, but wrapped in an object. The suggest-handler
        // above will recognize this and use merge this object with the request parameters, so
        // we can directly tell it to use our input as a value column filter instead of a regular
        // suggest string.
        $valueFilterParam = UrlDataType::urlEncode($this->getTemplate()->getUrlFilterPrefix() . $this->getWidget()->getValueColumn()->getAttributeAlias());
        return "setSelectedKey({$valueJs}).fireSuggest({suggestValue: {'{$valueFilterParam}': {$valueJs}}})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'selectedKey';
    }
    
    protected function getModelNameForAutosuggest() : string
    {
        return 'suggest';
    }
}
?>