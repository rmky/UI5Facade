<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Exceptions\Widgets\WidgetLogicError;
use exface\Core\DataTypes\UrlDataType;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Interfaces\Widgets\iShowDataColumn;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;

/**
 * Generates sap.m.Input with tabular autosuggest and value help.
 *
 * @method \exface\Core\Widgets\InputComboTable\InputComboTable getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5InputComboTable extends UI5Input
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::init()
     */
    protected function init()
    {
        parent::init();
        
        // If the combo does not allow new values, we need to force the UI5 input to
        // check any input via autosuggest _before_ any other action is taken.
        // TODO this only works if there was no value before and needs to be
        // extended to work with changing values too.
        if (! $this->getWidget()->getAllowNewValues()) {
            if ($this->getWidget()->getMultiSelect() === false) {
                $missingKeyCheckJs = "oInput.getSelectedKey() === ''";
            } else {
                $missingKeyCheckJs = "oInput.getTokens().length === 0";
            }
            $onChange = <<<JS

                        var oInput = oEvent !== undefined ? oEvent.getSource() : sap.ui.getCore().byId('{$this->getId()}');
                        if (oInput.getValue() !== '' && $missingKeyCheckJs){
                            oInput.fireSuggest({suggestValue: {q: oInput.getValue()}});
                            oEvent.cancelBubble();
                            oEvent.preventDefault();
                            return false;
                        }
                        if (oInput.getValue() === '' && $missingKeyCheckJs){
                            oInput.setValueState(sap.ui.core.ValueState.None);
                        }
JS;
            $this->addOnChangeScript($onChange);
            
            // TODO explicitly prevent propagation of enter-events to stop data widgets
            // from autoreloading if enter was pressed to soon.
            $onEnter = <<<JS
                
                        var oInput = oEvent.srcControl;
                        if (oInput.getValue() !== '' && {$missingKeyCheckJs}){
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
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        $controller = $this->getController();
        
        if ($widget->isPreloadDataEnabled()) {
            $cols = '';
            foreach ($widget->getTable()->getColumns() as $col) {
                $cols .= $col->getDataColumnName() . ',';
            }
            $cols = rtrim($cols, ",");
            $controller->addOnDefineScript("exfPreloader.addPreload('{$widget->getTableObject()->getAliasWithNamespace()}', ['{$cols}'], [], '{$widget->getPage()->getUid()}', '{$widget->getTable()->getId()}');");
        }
        
        $controller->addMethod('onSuggest', $this, 'oEvent', $this->buildJsDataLoader('oEvent'));
        
        if (! $this->isValueBoundToModel() && $value = $widget->getValueWithDefaults()) {
            // If the widget value is set explicitly, we either set the key only or the 
            // key and the text (= value of the input)
            if ($widget->getValueText() === null || $widget->getValueText() === '') {
                $valueJs = '"' . $this->escapeJsTextValue($value) . '"';
                $value_init_js = <<<JS

        .{$this->buildJsSetSelectedKeyMethod($valueJs, null, true)}.fireChange({value: {$valueJs}})
JS;
            } else {
                $value_init_js = <<<JS

        .{$this->buildJsSetSelectedKeyMethod($this->escapeJsTextValue($value), $widget->getValueText())}
JS;
            }
        } elseif ($widget->getValueAttribute() !== $widget->getTextAttribute()) {
            // If the value is to be taken from a model, we need to check if both - key
            // and value are there. If not, the value needs to be fetched from the server.
            // NOTE: in sap.m.MultiInput there are no tokens yet, so we tell the getter
            // method not to rely on the explicitly!!!
            $missingValueJs = <<<JS
            var sKey = oInput.{$this->buildJsValueGetterMethod(false)};
            var sVal = oInput.getValue();
            if (sKey !== '' && sVal === '') {
                {$this->buildJsValueSetter('sKey')};
            }
JS;
            // Do the missing-text-check every time the model of the sap.m.Input changes
            $value_init_js = <<<JS

        .attachModelContextChange(function(oEvent) {
            var oInput = oEvent.getSource();
            $missingValueJs
        })
JS;
            // Also do the check with every prefill (the model-change-trigger for some reason does not
            // work on non-maximized dialogs, but this check does)
            $this->getController()->addOnViewPrefilledScript("setTimeout(function(){var oInput = sap.ui.getCore().byId('{$this->getId()}'); {$missingValueJs} }, 0);");
            
            // Finally, if the value is bound to model, but the text is not, all the above logic will only
            // work once, because after that one time, there will be a text (value) and it won't change
            // with the model. To avoid this, the following code will empty the value of the input every
            // time the selectedKey changes to empty. This happens at least before every prefill.
            // NOTE: without setTimeout() the oInput is sometimes not initialized yet when init() of the
            // view is called in dialogs. In particular, this happens if the InputComboTable is a filter
            // in a table, that is the only direct child of a dialog.
            if ($this->isValueBoundToModel() && ! $this->getView()->getModel()->hasBinding($widget, 'value_text')) {
                $emptyValueWithKeyJs = <<<JS

            setTimeout(function(){
                var oInput = sap.ui.getCore().byId('{$this->getId()}');
                var oModel = oInput.getModel();
                var oKeyBinding = new sap.ui.model.Binding(oModel, '{$this->getValueBindingPath()}', oModel.getContext('{$this->getValueBindingPath()}'));
                oKeyBinding.attachChange(function(){
                    if (oInput.getSelectedKey() == '') {
                        if (oInput.destroyTokens !== undefined) {
                            oInput.destroyTokens();
                        }
                        oInput.setValue('');
                    }
                });
            }, 0);
JS;
                $this->getController()->addOnInitScript($emptyValueWithKeyJs);
            }
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
            /* @var $element \exface\UI5Facade\Facades\Elements\UI5DataColumn */
            $element = $this->getFacade()->getElement($col);
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
        
        $control = $widget->getMultiSelect() ? 'sap.m.MultiInput' : 'sap.m.Input';
        
        if ($widget->isRelation()) {
            $vhpOptions = "showValueHelp: true, valueHelpRequest: {$this->buildJsPropertyValueHelpRequest()}";
        } else {
            $vhpOptions = "showValueHelp: false";
        }
        
        return <<<JS

	   new {$control}("{$this->getId()}", {
			{$this->buildJsProperties()}
            {$this->buildJsPropertyType()}
			textFormatMode: "ValueKey",
			showSuggestion: true,
            maxSuggestionWidth: "400px",
            startSuggestion: function(){
                return sap.ui.Device.system.phone ? 0 : 1;
            }(),
            showTableSuggestionValueHelp: false,
            filterSuggests: false,
            suggest: {$this->getController()->buildJsMethodCallFromView('onSuggest', $this, $oControllerJs)},
            suggestionRows: {
                path: "{$this->getModelNameForAutosuggest()}>/rows",
                template: new sap.m.ColumnListItem({
				   cells: [
				       {$cells}
				   ]
				})
            },
            suggestionItemSelected: {$this->buildJsPropertySuggestionItemSelected($value_idx, $text_idx)}
			suggestionColumns: [
				{$columns}
            ],
            {$vhpOptions}
        })
        .setModel(new sap.ui.model.json.JSONModel(), "{$this->getModelNameForAutosuggest()}")
        {$value_init_js}
        {$this->buildJsPseudoEventHandlers()}
JS;
    }
                
    protected function buildJsPropertySuggestionItemSelected(int $valueColIdx, int $textColIdx) : string
    {
        return <<<JS
            function(oEvent){
                var oItem = oEvent.getParameter("selectedRow");
                if (! oItem) return;
				var aCells = oEvent.getParameter("selectedRow").getCells();
                var oInput = oEvent.getSource();
                oInput.{$this->buildJsSetSelectedKeyMethod("aCells[ {$valueColIdx} ].getText()", "aCells[ {$textColIdx} ].getText()")};
                oInput.setValueState(sap.ui.core.ValueState.None);
			},
JS;
    }
       
    /**
     * Returns the value of the property valueHelpRequest.
     * 
     * @return string
     */
    protected function buildJsPropertyValueHelpRequest($oControllerJs = 'oController') : string
    {
        // Currently, the value-help-button will simply trigger the autosuggest by firing the suggest
        // event with a special callback, that forces the input to show suggestions. This callback
        // is needed because just firing the suggest event will only show the suggestions if the
        // current text differs from the previous suggestion - don't know if this is a feature or
        // a bug. But with an explicit .showItems() it works well.
/*        return <<<JS
            function(oEvent) {
                var oInput = oEvent.getSource();
                {$this->buildJsBusyIconShow()};
                
                var sVal = oInput.getValue();
                if (sVal === undefined || sVal === '') {
                    sVal = ' ';
                }
                
                oInput.fireSuggest({
                    suggestValue: sVal, 
                    onLoaded: function(){
                        sap.ui.getCore().byId('{$this->getId()}')
                        .showItems(function(oItem){
                            return oItem;
                        })
                    }
                });
            },

JS;
*/
        $btn = $this->getWidget()->getLookupButton();
        /* @var $btnEl \exface\UI5Facade\Facades\Elements\UI5Button */
        $btnEl = $this->getFacade()->getElement($btn);
        
        return <<<JS

            function(oEvent) {
                if (sap.ui.getCore().byId('{$btnEl->getId()}') === undefined) {
                    var oLookupButton = {$btnEl->buildJsConstructor()};
                    {$this->getController()->getView()->buildJsViewGetter($this)}.addDependent(oLookupButton);
                }
                {$btnEl->buildJsClickEventHandlerCall()}
            },

JS;
        
        return $btnEl->buildJsClickViewEventHandlerCall() . ',';
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
     * @param string $oEventJs
     * @return string
     */
    protected function buildJsDataLoader(string $oEventJs = 'oEvent') : string
    {
        $widget = $this->getWidget();
        $configuratorElement = $this->getFacade()->getElement($widget->getTable()->getConfiguratorWidget());
        $serverAdapter = $this->getFacade()->getElement($widget->getTable())->getServerAdapter();
        $delim = json_encode($widget->getMultiSelectValueDelimiter());
        
        // NOTE: in sap.m.MultiInput there are no tokens yet, so we tell the getter
        // method not to rely on the explicitly!!!
        $onSuggestLoadedJs = <<<JS
                            
                if (silent) {
                    var data = oModel.getProperty('/rows');
                    var curKey = oInput.{$this->buildJsValueGetterMethod(false)};
                    var curKeys = curKey.split({$delim});
                    var iRowsCnt = parseInt(oModel.getProperty("/recordsTotal"));
                    var aFoundKeys = [];
                    if (iRowsCnt === 1 && (curKey === '' || data[0]['{$widget->getValueColumn()->getDataColumnName()}'] == curKey)) {
                        oInput.{$this->buildJsSetSelectedKeyMethod("data[0]['{$widget->getValueColumn()->getDataColumnName()}']", "data[0]['{$widget->getTextColumn()->getDataColumnName()}']")}
                        oInput.closeSuggestions();
                        oInput.setValueState(sap.ui.core.ValueState.None);
                    } else if (iRowsCnt > 0 && iRowsCnt === curKeys.length && oInput.addToken !== undefined) {
                        oInput.destroyTokens();
                        curKeys.forEach(function(sKey) {
                            sKey = sKey.trim();
                            data.forEach(function(oRow) {
                                if (oRow['{$widget->getValueColumn()->getDataColumnName()}'] == sKey) {
                                    oInput.addToken(new sap.m.Token({key: sKey, text: oRow['{$widget->getTextColumn()->getDataColumnName()}']}));
                                    aFoundKeys.push(sKey);
                                }
                            });
                        });
                        oInput.closeSuggestions();
                        if (aFoundKeys.length === curKeys.length) {
                            oInput.setValueState(sap.ui.core.ValueState.None);
                        } else {
                            oInput.setValueState(sap.ui.core.ValueState.Error);
                        }
                    } else {
                        oInput.setSelectedKey("");
                        oInput.setValueState(sap.ui.core.ValueState.Error);
                    }
                }
                {$this->buildJsBusyIconHide()}

                if (oSuggestTable) {
                    oSuggestTable.setBusy(false);
                }
                
JS;
        
        return <<<JS

                var oInput = {$oEventJs}.getSource();
                var oSuggestTable = sap.ui.getCore().byId('{$this->getId()}-popup-table');
                var q = {$oEventJs}.getParameter("suggestValue");
                var fnCallback = {$oEventJs}.getParameter("onLoaded");
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
				    start: 0,
                    data: {$configuratorElement->buildJsDataGetter($widget->getTable()->getLazyLoadingAction(), true)}
                };
                $.extend(params, qParams);

                var oModel = oInput.getModel('{$this->getModelNameForAutosuggest()}');
                
                if (fnCallback) {
                    oModel.attachRequestCompleted(function(){
                        fnCallback();
                        oModel.detachRequestCompleted(fnCallback);
                    });
                }

                if (silent) {
                    {$this->buildJsBusyIconShow()}
                }

                if (oSuggestTable) {
                    oSuggestTable.setBusyIndicatorDelay(0).setBusy(true);
                }
                
                {$serverAdapter->buildJsServerRequest($widget->getLazyLoadingAction(), 'oModel', 'params', $onSuggestLoadedJs, $this->buildJsBusyIconHide())}

JS;
    }
    
    /**
     * The value and selectedKey properties of input controls do not seem to work before
     * a model is bound, so we set initial value programmatically at the end of the constructor.
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsPropertyValue()
     */
    protected function buildJsPropertyValue()
    {
        $widget = $this->getWidget();
        $model = $this->getView()->getModel();
        if ($model->hasBinding($widget, 'value_text')) {
            $valueBinding = ' value: "{' . $model->getBindingPath($widget, 'value_text') . '}",';
        }
        if ($this->isValueBoundToModel()) {
            // NOTE: for some reason putting the value binding _BEFORE_ the key binding is important!
            // Otherwise the key is not set sometimes...
            return $valueBinding . 'selectedKey: ' . $this->buildJsValueBinding() . ',';
        }
        return '';
    }
    
    /**
     * Returns the JS method to get the current value.
     * 
     * The additional parameter $useTokensIfMultiSelect controls, how sap.m.MultiInput is handled.
     * For some reason it's methods getTokens() and getSelectedKey() are not in sync. So if the
     * tokens are not initialized yet, getSelectedKey() must be used - that's the one that is
     * bound to the model actually.
     * 
     * @param bool $useTokensIfMultiSelect
     * @return string
     * 
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod(bool $useTokensIfMultiSelect = true)
    {
        if ($this->getWidget()->getMultiSelect() === false || $useTokensIfMultiSelect === false) {
            if ($this->getWidget()->getValueAttribute() === $this->getWidget()->getTextAttribute()) {
                return "getValue()";
            } else {            
                return "getSelectedKey()";
            }
        } else {
            $delim = $this->getWidget()->getMultiSelectTextDelimiter();
            return "getTokens().reduce(function(sList, oToken, iIdx, aTokens){ return sList + (sList !== '' ? '$delim' : '') + oToken.getKey() }, '')";
        }
    }
    
    public function buildJsValueGetter($column = null, $row = null)
    {
        $selectedKeyGetter = parent::buildJsValueGetter();
        if (($column === null || $column == $this->getWidget()->getValueAttributeAlias()) && ($row === null || $row === 0)) {
            return $selectedKeyGetter;
        }
        
        return <<<JS
function(){
    var sSelectedKey = {$selectedKeyGetter};
    if (sSelectedKey === undefined || sSelectedKey === undefined === '' || sSelectedKey === null) {
        return undefined;
    }
    var oInput = sap.ui.getCore().byId('{$this->getId()}');
    var oModel = oInput.getModel('{$this->getModelNameForAutosuggest()}');
    
    var oItem = oModel.getData().rows.find(function(element, index, array){
        return element['{$this->getWidget()->getValueAttributeAlias()}'] == sSelectedKey;
    });

    return oItem['$column'];
}()

JS;
    }
    
    /**
     * Returns a special parameter for the oInput.fireSuggest() method, that
     * cases a silent lookup of the value matching the given key - without actually
     * opening the suggestions.
     * 
     * @return string
     */
    protected function buildJsFireSuggestParamForSilentKeyLookup(string $keyJs) : string
    {
        $filterParam = UrlDataType::urlEncode($this->getFacade()->getUrlFilterPrefix() . $this->getWidget()->getValueColumn()->getAttributeAlias());
        return <<<JS
{
                    suggestValue: {
                        '{$filterParam}': $keyJs
                    }
                }
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueSetter()
     */
    public function buildJsValueSetter($valueJs)
    {
        // After setting the key, we need to fetch the corresponding text value, so we use a trick
        // and pass the given value not directly, but wrapped in an object. The suggest-handler
        // above will recognize this and use merge this object with the request parameters, so
        // we can directly tell it to use our input as a value column filter instead of a regular
        // suggest string.
        return "(function(){
            var oInput = sap.ui.getCore().byId('{$this->getId()}');
            var val = {$valueJs};
            if (val == undefined || val === null || val === '') {
                oInput.{$this->buildJsEmptyMethod('val', '""')};
            } else {
                if (oInput.destroyTokens !== undefined) {
                    oInput.destroyTokens();
                }
                oInput
                .setSelectedKey(val)
                .fireSuggest({$this->buildJsFireSuggestParamForSilentKeyLookup('val')});
            }
            oInput.fireChange({
                value: val
            });
            return oInput;
        })()";
    }
    
    /**
     * There is no value setter method for this class, because the logic of the value setter
     * (see above) cannot be easily packed into a single method to be called on the control.
     * 
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        throw new FacadeLogicError('Cannot use UI5InputComboTable::buildJsValueSetterMethod() - use buildJsValueSetter() instead!');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValueBindingPropertyName()
     */
    public function buildJsValueBindingPropertyName() : string
    {
        return 'selectedKey';
    }
    
    protected function getModelNameForAutosuggest() : string
    {
        return 'suggest';
    }
    
    protected function buildJsEmptyMethod() : string
    {
        if ($this->getWidget()->getMultiSelect() === false) {
            return "setValue('').setSelectedKey('')";
        } else {
            return "setValue('').setSelectedKey('').destroyTokens()";
        }
    }
    
    /**
     * Returns a chained method call to set the key and value for the Input control.
     * 
     * If $lookupKeyValue is set to TRUE, a silenced suggest event will be fired to
     * request the value from the server based on the given $keyJs. This value will
     * will overwrite $valueJs!
     * 
     * In contrast to the value setter this method does not trigger a change event!!!
     * 
     * @param string $keyJs
     * @param string $valueJs
     * @param bool $lookupKeyValue
     * @return string
     */
    protected function buildJsSetSelectedKeyMethod(string $keyJs, string $valueJs = null, bool $lookupKeyValue = false) : string
    {
        if ($this->getWidget()->getMultiSelect() === false) {
            if ($valueJs !== null) {
                $setValue = "setValue($valueJs).";
            } else {
                $setValue = '';
            }
            $js = "{$setValue}setSelectedKey($keyJs)";
        } else {
            $js = "setSelectedKey($keyJs).addToken(new sap.m.Token({key: $keyJs, text: $valueJs}))";
        }
        
        if ($lookupKeyValue === true) {
            $js .= ".fireSuggest({$this->buildJsFireSuggestParamForSilentKeyLookup($keyJs)})";
        }
        
        return $js;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsValidatorCheckDataType()
     */
    protected function buildJsValidatorCheckDataType(string $valueJs, string $onFailJs, DataTypeInterface $type) : string
    {
        $widget = $this->getWidget();
        if ($widget->getMultiSelect() === false) {
            return parent::buildJsValidatorCheckDataType($valueJs, $onFailJs, $type);
        } else {
            $partValidator = parent::buildJsValidatorCheckDataType('part', $onFailJs, $type);
            return <<<JS
if ($valueJs !== undefined) {
    $valueJs.toString().split("{$widget->getMultiSelectValueDelimiter()}").forEach(part => {
        $partValidator
    });
}

JS;
        }
    }
    
    /**
     * Returns a JS snippet, that can set data given in the same structure as the data getter would produce.
     *
     * This is basically the opposite of buildJsDataGetter(). The input must be valid JS code representing
     * or returning a JS data sheet.
     *
     * For example, this code will extract data from a table and put it into a container:
     * $container->buildJsDataSetter($table->buildJsDataGetter())
     *
     * @param string $jsData
     * @return string
     */
    public function buildJsDataSetter(string $jsData) : string
    {
        $widget = $this->getWidget();
        
        $parentSetter = parent::buildJsDataSetter($jsData);
        $colName = $this->getWidget()->getValueAttributeAlias();
    
        // The '!' in front of the IFFE is required because it would not get executed stand alone
        // resulting in a "SyntaxError: Function statements require a function name" instead.
        return <<<JS

!function() {
    var oData = {$jsData};
    if (oData !== undefined && Array.isArray(oData.rows) && oData.rows.length > 0) {
        if (oData.oId == "{$this->getWidget()->getTable()->getMetaObject()->getId()}") {

             if (oData.rows[0]['{$widget->getTextColumn()->getDataColumnName()}'] != undefined){
                var oInput = sap.ui.getCore().byId("{$this->getId()}");
                oInput.{$this->buildJsEmptyMethod()};
                oData.rows.forEach(function(oRow){
                    oInput.{$this->buildJsSetSelectedKeyMethod("oRow['{$colName}']", "oRow['{$widget->getTextColumn()->getDataColumnName()}']")};
                });
                                     
            } else {
                var val;
                if (oData.rows.length === 1) {
                   val = oData.rows[0]['{$colName}'];
                } else if (oData.rows.length > 1) {
                    var vals = [];
                    oData.rows.forEach(function(oRow) {
                        vals.push(oRow['{$colName}']);
                    });
                    val = vals.join('{$widget->getAttribute()->getValueListDelimiter()}');
                }
                {$this->buildJsValueSetter("val")}
            }
    
    
        } else {
            $parentSetter;
        }
    }
}()

JS;
    }
    
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        // If the object of the action is the same as that of the widget, treat
        // it as a regular input.
        if ($action === null || $this->getMetaObject()->is($action->getMetaObject()) || $action->getInputMapper($this->getMetaObject()) !== null) {
            return parent::buildJsDataGetter($action);
        }
        
        $widget = $this->getWidget();
        // If it's another object, we need to decide, whether to place the data in a 
        // subsheet.
        if ($action->getMetaObject()->is($widget->getTableObject())) {
            // FIXME not sure what to do if the action is based on the object of the table.
            // This should be really important in lookup dialogs, but for now we just fall
            // back to the generic input logic.
            return parent::buildJsDataGetter($action);
        } elseif ($relPath = $widget->findRelationPathFromObject($action->getMetaObject())) {
            $relAlias = $relPath->toString();
        }
        
        if ($relAlias === null || $relAlias === '') {
            throw new WidgetConfigurationError($widget, 'Cannot use data from widget "' . $widget->getId() . '" with action on object "' . $action->getMetaObject()->getAliasWithNamespace() . '": no relation can be found from widget object to action object', '7CYA39T');
        }
        
        if ($widget->getMultiSelect() === false) { 
            $rows = "[{ {$widget->getDataColumnName()}: {$this->buildJsValueGetter()} }]";
        } else {
            $delim = str_replace("'", "\\'", $this->getWidget()->getMultiSelectTextDelimiter());
            $rows = <<<JS
                            function(){
                                var aVals = ({$this->buildJsValueGetter()}).split('{$delim}');
                                var aRows = [];
                                aVals.forEach(function(sVal) {
                                    if (sVal !== undefined && sVal !== null && sVal !== '') {
                                        aRows.push({
                                            {$widget->getDataColumnName()}: sVal
                                        });
                                    }
                                })
                                return aRows;
                            }()

JS;
        }
        
        return <<<JS
        
            {
                oId: '{$action->getMetaObject()->getId()}',
                rows: [
                    {
                        '{$relAlias}': {
                            oId: '{$widget->getMetaObject()->getId()}',
                            rows: {$rows}
                        }
                    }
                ]
            }
            
JS;
    }
}
?>