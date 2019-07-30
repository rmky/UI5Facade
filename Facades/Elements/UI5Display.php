<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Display;
use exface\UI5Facade\Facades\Interfaces\UI5BindingFormatterInterface;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Widgets\DataColumn;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsValueScaleTrait;

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
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->registerColorResolver($oControllerJs);
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        if ($this->getWidget()->getValueDataType() instanceof BooleanDataType) {
            if ($this->getWidget()->getParent() instanceof DataColumn) {
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
        return 'wrapping: false,';
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
        if ($this->getWidget()->getParent() instanceof DataColumn) {
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
    
    protected function registerColorResolver(string $oControllerJs = 'oController') : UI5Display
    {
        $controller = $this->getController();
        
        // Create a controller method to compute the color for the current value and set it for the control
        $controller->addMethod('resolveColor', $this, '', $this->buildJsColorScaleResolver());
        // Make sure, the color resolver is called when the control is initially rendered
        $this->addPseudoEventHandler('onAfterRendering', $oControllerJs . '.' . $controller->buildJsMethodName('resolveColor', $this) . '()');
        
        return $this;
    }
    
    protected function buildJsColorScaleResolver() : string
    {
        $widget = $this->getWidget();
        if ($widget->hasColorScale() === false) {
            return '';
        }
        
        $semColsJs = json_encode($this->getColorScaleSemanticColorMap());
        
        return <<<JS
        
    var sColor = {$this->buildJsScaleResolverForNumbers($this->buildJsValueGetter(), $widget->getColorScale())};
    var sValueColor;
    if (sColor.startsWith('~')) {
        var oColorScale = {$semColsJs};
        sValueColor = oColorScale[sColor];
        {$this->buildJsColorSetter('sValueColor', true)}
    } else if (sColor) {
        {$this->buildJsColorSetter('sColor', false)}
    }
    
JS;
    }
        
    protected function buildJsColorSetter(string $colorValueJs, bool $isSemanticColor) : string
    {
        if ($isSemanticColor) {
            return "sap.ui.getCore().byId('{$this->getId()}').setState({$colorValueJs});";
        } else {
            // TODO
            return <<<JS

        console.warn('Cannot set color "' + {$colorValueJs} + '" - only UI5 semantic colors currently supported!');

JS;
        }
    }
        
    protected function getColorScaleSemanticColorMap() : array
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
            $this->addOnBindingChangeScript($this->buildJsValueBindingPropertyName(), $this->getController()->buildJsEventHandler($this, 'change', false));
            $this->onChangeHandlerRegistered = true;
        }
        return parent::addOnChangeScript($script);
    }
}
?>