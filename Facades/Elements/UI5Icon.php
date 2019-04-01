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
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $icon = $this->buildJsConstructorForIcon();
        if (! $icon) {
            return $this->buildJsConstructorForMainControl($oControllerJs);
        }
        
        switch ($this->getWidget()->getIconPosition()) {
            case EXF_ALIGN_RIGHT: return <<<JS
            
        new sap.m.HBox({
            justifyContent: "SpaceAround",
            alignItems: "Center",
            items: [
                {$this->buildJsConstructorForMainControl($oControllerJs)},
                {$icon}
                
            ]
        })
        
JS;
            case EXF_ALIGN_CENTER: return <<<JS
            
        new sap.m.VBox({
            width: "100%",
            alignItems: "Center",
            items: [
                {$icon}.addStyleClass("sapUiSmallMargin"),
                {$this->buildJsConstructorForMainControl($oControllerJs)}
            ]
        })
        
JS;
            default: return <<<JS

        new sap.m.HBox({
            justifyContent: "SpaceAround",
            alignItems: "Center",
            items: [
                {$icon},
                {$this->buildJsConstructorForMainControl($oControllerJs)}
            ]
        })

JS;
        }
    }
    
    public function buildJsConstructorForIcon() : string
    {
        $widget = $this->getWidget();
        $icon = $widget->getIcon();
        if (! $icon) {
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
                src: "{$this->getIconSrc($icon)}",
                {$size}
            })

JS;
    }
}