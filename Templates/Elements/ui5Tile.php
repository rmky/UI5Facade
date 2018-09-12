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
            $tileContent = <<<JS

    tileContent: [
        new sap.m.TileContent({
            content: [
                {$tileContentConstructor}
            ]
        })
    ]
JS;
        } elseif ($subtitle = $widget->getSubtitle()) {
            $tileContent = <<<JS

    tileContent: [
        new sap.m.TileContent({
			content: [
				new sap.m.FeedContent({
					contentText: "{$subtitle}"
				})
			]
		})
    ]
JS;
        }
        
        return <<<JS

new sap.m.GenericTile("{$this->getId()}", {
    {$header}
    {$subheader}
    {$press}
    {$tileContent}
}).addStyleClass("sapUiTinyMarginBegin sapUiTinyMarginTop tileLayout")
JS;
    }
}
