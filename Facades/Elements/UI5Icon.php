<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Icon;

/**
 * Generates sap.m.Text controls for Text widgets
 * 
 * @method Icon getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Icon extends UI5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsConstructor()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController') : string
    {
        $icon = $this->buildJsConstructorForIcon();
        
        if ($this->getWidget()->hasValueWidget() === true) {
            $valueElement = $this->getFacade()->getElement($this->getWidget()->getValueWidget());
            switch ($this->getWidget()->getIconPosition()) {
                case EXF_ALIGN_RIGHT: $js = <<<JS
                
            new sap.m.HBox({
                justifyContent: "SpaceAround",
                alignItems: "Center",
                items: [
                    {$valueElement->buildJsConstructorForMainControl($oControllerJs)},
                    {$icon}
                    
                ]
            })
            
JS;
                case EXF_ALIGN_CENTER: $js = <<<JS
                
            new sap.m.VBox({
                width: "100%",
                alignItems: "Center",
                items: [
                    {$icon}.addStyleClass("sapUiSmallMargin"),
                    {$valueElement->buildJsConstructorForMainControl($oControllerJs)}
                ]
            })
            
JS;
                default: $js = <<<JS
    
            new sap.m.HBox({
                justifyContent: "SpaceAround",
                alignItems: "Center",
                items: [
                    {$icon},
                    {$valueElement->buildJsConstructorForMainControl($oControllerJs)}
                ]
            })
    
JS;
            }
        } else {
            $js = $icon;
        }
        return $js;
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsConstructorForIcon() : string
    {
        $widget = $this->getWidget();
        
        $iconSrc = $this->buildJsPropertyValue();
        if ($iconSrc === '') {
            return '';
        }
        
        switch (strtolower($widget->getIconSize())) {
            case EXF_TEXT_SIZE_BIG: $size = 'size: "36px",'; break;
            case EXF_TEXT_SIZE_SMALL: $size = 'size: "12px",'; break;
            case EXF_TEXT_SIZE_NORMAL: 
            default: $size = '';
        }
        
        return <<<JS

            new sap.ui.core.Icon({
                {$iconSrc}
                {$size}
            })

JS;
    }
    
    /**
    * Returns the value property with property name and value followed by a comma.
    *
    * @return string
    */
    protected function buildJsPropertyValue()
    {
        $src = $this->buildJsValue();
        if ($src === '') {
            return '';
        }
        return <<<JS
            src: {$src},
JS;
    }
    
    /**
     * Returns inline javascript code for the value of the value property (without the property name).
     *
     * Possible results are a quoted JS string, a binding expression or a binding object.
     *
     * @return string
     */
    public function buildJsValue()
    {
        if ($staticIcon = $this->getWidget()->getIcon()) {
            return '"' . $this->getIconSrc($staticIcon) . '"';
        }
        return parent::buildJsValue();
    }
   
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsLabelWrapper()
     */
    protected function buildJsLabelWrapper($element_constructor)
    {
        if ($this->getCaption() === '') {
            $widget = $this->getWidget();
            if ($widget->hasValueWidget() === true) {
                $widget->setCaption($widget->getValueWidget()->getCaption());
            } else {
                return $element_constructor;
            }
        }
        return parent::buildJsLabelWrapper($element_constructor);
    }
}