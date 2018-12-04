<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Tile;

/**
 * Tile widget for OpenUI5-Template.
 * 
 * @method Tile getWidget()
 * 
 * @author SFL
 *
 */
class ui5Tile extends ui5Button
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Button::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $widget = $this->getWidget();
        
        $header = $widget->getTitle() ? 'header: "' . $widget->getTitle() . '",' : '';
        $handler = $this->buildJsClickViewEventHandlerCall();
        $press = $handler !== '' ? 'press: ' . $handler . ',' : '';
        
        if ($widget->hasDisplayWidget()) {
            $subheader = $widget->getSubtitle() ? 'subheader: "' . $widget->getSubtitle() . '",' : '';
            $tileContentConstructor = $this->getTemplate()->getElement($widget->getDisplayWidget())->buildJsConstructor();
        } else {
            $subtitle = $widget->getSubtitle();
            $icon = $widget->getIcon();
            if ($subtitle /*&& ! $icon*/) {
                $tileContentConstructor = <<<JS
    
                    new sap.m.FeedContent({
    					contentText: "{$subtitle}"
    				}),
    
JS;
            } elseif ($icon) {
                /*$subheader = 'subheader: "' . $subtitle . '",';
                $tileContentConstructor = $this->buildJsIconContent();*/
            }
        } 
        
        return <<<JS

new sap.m.GenericTile("{$this->getId()}", {
    {$header}
    {$subheader}
    {$press}
    tileContent: [
        new sap.m.TileContent({
            content: [
                {$tileContentConstructor}
            ]
        })
    ]
}).addStyleClass("sapUiTinyMarginBegin sapUiTinyMarginTop tileLayout")
JS;
    }
    
    protected function buildJsIconContent() : string
    {
        if ($icon = $this->getWidget()->getIcon()) {
            return <<<JS
        
                new sap.m.ImageContent({
            		src: "{$this->getIconSrc($icon)}"
            	})

JS;
        }
        return '';
    }
}
