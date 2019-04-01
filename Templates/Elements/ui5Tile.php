<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Tile;

/**
 * Tile widget for OpenUI5-Facade.
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
     * @see \exface\UI5Facade\Facades\Elements\ui5Button::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $widget = $this->getWidget();
        
        $header = $this->getCaption() ? 'header: "' . $widget->getTitle() . '",' : '';
        $handler = $this->buildJsClickViewEventHandlerCall();
        $press = $handler !== '' ? 'press: ' . $handler . ',' : '';
        $tileClass = '';
        
        if ($widget->getWidth()->isUndefined() === false) {
            $container = $this->getFacade()->getElement($widget->getParent());
            if (($container instanceof ui5Tiles) && $container->isStretched() === true) {
                $tileClass .= ' exf-stretched';
                switch ($widget->getWidth()->getValue()) {
                    case '25%': $tileClass .= ' exf-col-3'; break;
                    case '33%': $tileClass .= ' exf-col-4'; break;
                    case '50%': $tileClass .= ' exf-col-6'; break;
                    case '100%': $tileClass .= ' exf-col-12'; break;
                }
            }
        }
        
        if ($widget->hasDisplayWidget()) {
            $elem = $this->getFacade()->getElement($widget->getDisplayWidget());
            if (($elem instanceof ui5Icon) && ($icon = $elem->buildJsConstructorForIcon())) {
                $tileContentConstructor = <<<JS
            
                    new sap.m.VBox({
                        width: "100%",
                        alignItems: "Center",
                        items: [
                            {$icon}.addStyleClass("sapUiSmallMargin"),
                            new sap.m.Title({
                                text: "{$widget->getCaption()}"
                            })
                        ]
                    })
    
JS;
                $header = '';
                $tileClass .= " exf-icon-tile";
            } else {
                $subheader = $widget->getSubtitle() ? 'subheader: "' . $widget->getSubtitle() . '",' : '';
                $tileContentConstructor = $elem->buildJsConstructor();
            }
        } else {
            $subtitle = $widget->getSubtitle();
            $icon = $widget->getShowIcon(false) ? $widget->getIcon() : null;
            if ($subtitle && ! $icon) {
                $tileContentConstructor = <<<JS
    
                    new sap.m.FeedContent({
    					contentText: "{$subtitle}"
    				}),
    
JS;
            } elseif ($icon) {
                $subheader = 'subheader: "' . $subtitle . '",';
                $tileContentConstructor = $this->buildJsIconContent();
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
}).addStyleClass("sapUiTinyMarginBegin sapUiTinyMarginTop tileLayout {$tileClass}")
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
