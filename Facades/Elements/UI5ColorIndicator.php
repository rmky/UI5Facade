<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\ColorIndicator;
use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;
use exface\Core\CommonLogic\Constants\Colors;

/**
 * Renders a ColorIndicator widget as sap.ui.core.Icon with a colored circle.
 * 
 * @method ColorIndicator getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class UI5ColorIndicator extends UI5Display
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        $widget = $this->getWidget();
        $colorOnly = true;
        if ($widget instanceof ColorIndicator) {
            if ($widget->hasColorConditions() === true) {
                $this->getWorkbench()->getLogger()->logException(new FacadeUnsupportedWidgetPropertyWarning('Property color_conditions currently not supported for widget ' . $widget->getWidgetType() . ' in the UI5 facade.'));
            }
            // See if the user forced to not use color-only mode
            $colorOnly = $widget->getColorOnly($colorOnly);
        }
        
        $icon = <<<JS
        
        new sap.ui.core.Icon("{$this->getid()}", {
            src: "sap-icon://circle-task-2",
            color: {$this->buildJsColorValue()},
            {$this->buildJsProperties()}
    	})
    	
JS;
            
        if ($colorOnly === true) {
            return $icon;
        } else {
            $text = parent::buildJsConstructorForMainControl($oControllerJs);
            return <<<JS

        new sap.m.FlexBox({
            alignItems: "Center",
            items: [
                $icon.addStyleClass("sapUiSmallMarginEnd"),
                $text
            ]
        })

JS;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyAlignment()
     */
    protected function buildJsPropertyAlignment()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyState()
     */
    protected function buildJsPropertyState() : string
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsColorValue()
     */
    protected function buildJsColorValue() : string
    {
        if (! $this->isValueBoundToModel()) {
            $value = $this->buildJsColorValueNoColor(); // TODO
        } else {
            $semColsJs = json_encode($this->getColorScaleSemanticColorMap());
            $bindingOptions = <<<JS
                formatter: function(value){
                    var sColor = {$this->buildJsScaleResolver('value', $this->getWidget()->getColorScale(), $this->getWidget()->isColorScaleRangeBased())};
                    if (sColor.startsWith('~')) {
                        var oColorScale = {$semColsJs};
                        return oColorScale[sColor];
                    } 
                    return sColor || {$this->buildJsColorValueNoColor()};
                }
                
JS;
            $value = $this->buildJsValueBinding($bindingOptions);
        }
        return $value;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsColorValueNoColor()
     */
    protected function buildJsColorValueNoColor() : string
    {
        return '"transparent"';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getTooltip()";
    }
    
    protected function getColorScaleSemanticColorMap() : array
    {
        $semCols = [];
        foreach (Colors::getSemanticColors() as $semCol) {
            switch ($semCol) {
                case Colors::SEMANTIC_ERROR: $ui5Color = 'Negative'; break;
                case Colors::SEMANTIC_WARNING: $ui5Color = 'Critical'; break;
                case Colors::SEMANTIC_OK: $ui5Color = 'Positive'; break;
                case Colors::SEMANTIC_INFO: $ui5Color = 'Neutral'; break;
            }
            $semCols[$semCol] = $ui5Color;
        }
        return $semCols;
    }
}