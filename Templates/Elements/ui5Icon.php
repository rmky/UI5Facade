<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Icon;

/**
 * Generates sap.m.Text controls for Text widgets
 * 
 * @method Icon getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Icon extends ui5Display
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $icon = $this->buildJsConstructorForIcon();
        if ($icon) {
            return <<<JS

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
            
        return $this->buildJsConstructorForMainControl($oControllerJs);
    }
    
    public function buildJsConstructorForIcon() : string
    {
        $widget = $this->getWidget();
        $icon = $widget->getIcon();
        if (! $icon) {
            return '';
        }
        
        switch (strtolower($widget->getSize())) {
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