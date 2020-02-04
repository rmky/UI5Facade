<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Display;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsValueScaleTrait;
use exface\Core\Interfaces\Widgets\iHaveColorScale;
use exface\UI5Facade\Facades\Interfaces\UI5ControllerInterface;

/**
 * Generates sap.m.Text controls for Display widgets.
 * 
 * @method Display getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Display extends UI5Value
{
    use JsValueScaleTrait;
    
    private $alignmentProperty = null;
    
    private $onChangeHandlerRegistered = false;
    
    private $wrap = false;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->registerExternalModules($this->getController());
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        if ($widget->getValueDataType() instanceof BooleanDataType) {
            if ($this->getWidget()->isInTable() === true) {
                $icon_yes = 'sap-icon://accept';
                $icon_no = '';
                $icon_width = '"100%"';
            } else {
                $icon_yes = 'sap-icon://message-success';
                $icon_no = 'sap-icon://border';
                $icon_width = '"14px"';
            }
            $js = <<<JS

        new sap.ui.core.Icon({
            width: {$icon_width},
            {$this->buildJsPropertyTooltip()}
            src: {$this->buildJsValueBinding('formatter: function(value) {
                    if (value === "1" || value === "true" || value === 1 || value === true) return "' . $icon_yes . '";
                    else return "' . $icon_no . '";
                }')}
        })

JS;
        } elseif($widget instanceof iHaveColorScale && $widget->hasColorScale()) {
            $objStatus = new UI5ObjectStatus($widget, $this->getFacade());
            $objStatus->setTitle('');
            $objStatus->setValueBindingPrefix($this->getValueBindingPrefix());
            $js = $objStatus->buildJsConstructorForMainControl($oControllerJs);
        } else {
            $js = parent::buildJsConstructorForMainControl();
        }

        // TODO #binding store values in real model
        if(! $this->isValueBoundToModel()) {
            $value = $this->escapeJsTextValue($this->getWidget()->getValue());
            $value = '"' . str_replace("\n", '', $value) . '"';
            $js .= <<<JS

            .setModel(function(){
                var oModel = new sap.ui.model.json.JSONModel();
                oModel.setProperty("/{$this->getWidget()->getDataColumnName()}", {$value});
                return oModel;
            }())
JS;
        }
        
        return $js;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ValueBindingInterface::buildJsValueBindingOptions()
     */
    public function buildJsValueBindingOptions()
    {
        return $this->getValueBindingFormatter()->buildJsBindingProperties();
    }
    
    /**
     * 
     * @return UI5BindingFormatterInterface
     */
    protected function getValueBindingFormatter()
    {
        return $this->getFacade()->getDataTypeFormatterForUI5Bindings($this->getWidget()->getValueDataType());
    }
    
    /**
     * Sets the alignment for the content within the display: Begin, End, Center, Left or Right.
     * 
     * @param $propertyValue
     * @return UI5Display
     */
    public function setAlignment($propertyValue)
    {
        $this->alignmentProperty = $propertyValue;
        return $this;
    }

    /**
     * 
     * @return string
     */
    protected function buildJsPropertyAlignment()
    {
        return $this->alignmentProperty ? 'textAlign: ' . $this->alignmentProperty . ',' : '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsProperties()
     */
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . <<<JS
            {$this->buildJsPropertyWidth()}
            {$this->buildJsPropertyHeight()}
            {$this->buildJsPropertyAlignment()}
            {$this->buildJsPropertyWrapping()}
JS;
    }
            
    /**
     * Returns "wrapping: false/true," with tailing comma.
     * 
     * @return string
     */
    protected function buildJsPropertyWrapping()
    {
        return 'wrapping: ' . ($this->getWrapping() === true ? 'true' : 'false'). ',';
    }
    
    /**
     * {@inheritDoc}
     * 
     * If the display is used as cell widget in a DataColumn, the tooltip will
     * contain the value instead of a description, because ui5 tables tend to
     * cut off long values on smaller screens. On the other hande, the description 
     * is already there in the column header.
     * 
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsPropertyTooltip()
     */
    protected function buildJsPropertyTooltip()
    {
        if ($this->getWidget()->isInTable() === true) {
            if ($this->isValueBoundToModel()) {
                $value = $this->buildJsValueBinding('formatter: function(value){return (value === null || value === undefined) ? value : value.toString();},');
            } else {
                $value = $this->buildJsValue();
            }
            
            return 'tooltip: ' . $value .',';
        }
        
        return parent::buildJsPropertyTooltip();
    }
    
    public function buildJsValueSetterMethod($value)
    {
        return "setText({$value})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsPropertyVisibile()
     */
    protected function buildJsPropertyVisibile()
    {
        if (! $this->isVisible()) {
            return 'visible: false, ';
        }
        
        if ($this->getWidget()->getHideIfEmpty() === true) {
            if ($this->isValueBoundToModel() === true) {
                // If the value is bound to model, attach a change handler to the binding and
                // check if the element has a value on every change in the model.
                $hideOnChangeJs = <<<JS

                    var oModel = sap.ui.getCore().byId('{$this->getId()}').getModel();
                    var oBindingContext = new sap.ui.model.Binding(oModel, '{$this->getValueBindingPath()}', oModel.getContext('{$this->getValueBindingPath()}'));
                    oBindingContext.attachChange(function(oEvent){
                        if ({$this->buildJsValueGetter()}) {
                            sap.ui.getCore().byId('{$this->getId()}').setVisible(true);
                        } else {
                            sap.ui.getCore().byId('{$this->getId()}').setVisible(false);
                        }
                    });

JS;
                $this->getController()->addOnInitScript($hideOnChangeJs);
            } elseif ($this->getWidget()->hasValue() === false) {
                return 'visible: false, ';
            }
        }
        
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getText()";
    }
        
    protected function getColorSemanticMap() : array
    {
        $semCols = [];
        foreach (Colors::getSemanticColors() as $semCol) {
            switch ($semCol) {
                case Colors::SEMANTIC_ERROR: $ui5Color = 'Error'; break;
                case Colors::SEMANTIC_WARNING: $ui5Color = 'Warning'; break;
                case Colors::SEMANTIC_OK: $ui5Color = 'Success'; break;
                case Colors::SEMANTIC_INFO: $ui5Color = 'Information'; break;
            }
            $semCols[$semCol] = $ui5Color;
        }
        return $semCols;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::addOnChangeScript()
     */
    public function addOnChangeScript($script)
    {
        if ($this->isValueBoundToModel() && $this->onChangeHandlerRegistered === false) {
            $this->addOnBindingChangeScript($this->buildJsValueBindingPropertyName(), $this->getController()->buildJsEventHandler($this, self::EVENT_NAME_CHANGE, false));
            $this->onChangeHandlerRegistered = true;
        }
        return parent::addOnChangeScript($script);
    }
    
    protected function buildJsColorValue() : string
    {
        $widget = $this->getWidget();
        if (! ($widget instanceof iHaveColorScale && $widget->hasColorScale() !== false)) {
            return '';
        }
        
        if (! $this->isValueBoundToModel()) {
            $value = ''; // TODO
        } else {
            $semColsJs = json_encode($this->getColorSemanticMap());
            $bindingOptions = <<<JS
                formatter: function(value){
                    var sColor = {$this->buildJsScaleResolver('value', $widget->getColorScale(), $widget->isColorScaleRangeBased())};
                    var sValueColor;
                    var oCtrl = this;
                    if (sColor.startsWith('~')) {
                        var oColorScale = {$semColsJs};
                        return oColorScale[sColor];
                    } else if (sColor) {
                        {$this->buildJsColorCssSetter('oCtrl', 'sColor')}
                    }
                    return {$this->buildJsColorValueNoColor()};
                }
                
JS;
            $value = $this->buildJsValueBinding($bindingOptions);
        }
        return $value;
    }
    
    protected function buildJsColorValueNoColor() : string
    {
        return 'sap.ui.core.ValueState.None';
    }
    
    protected function buildJsColorCssSetter(string $oControlJs, string $sColorJs) : string
    {
        return "setTimeout(function(){ $oControlJs.$().css('color', $sColorJs); }, 0)";
    }
    
    /**
     *
     * @return bool
     */
    protected function getWrapping() : bool
    {
        return $this->wrap;
    }
    
    /**
     * 
     * @param bool $value
     * @return UI5Display
     */
    public function setWrapping(bool $value) : UI5Display
    {
        $this->wrap = $value;
        return $this;
    }
    
    public function registerExternalModules(UI5ControllerInterface $controller) : UI5AbstractElement
    {
        $this->getValueBindingFormatter()->registerExternalModules($controller);
        return $this;
    }
}
?>