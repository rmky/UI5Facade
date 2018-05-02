<?php
namespace exface\OpenUI5Template\Templates\Elements;

/**
 * Tile widget for OpenUI5-Template.
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
    public function buildJsConstructor($oController = 'oController') : string
    {
        $widget = $this->getWidget();
        
        $header = $widget->getTitle() ? 'header: "' . $widget->getTitle() . '",' : '';
        $subheader = $widget->getSubtitle() ? 'subheader: "' . $widget->getSubtitle() . '",' : '';
        $press = $this->buildJsClickFunction() ? 'press: function(){' . $this->buildJsClickFunctionName() . '()},' : '';
        if ($widget->hasDisplayWidget()) {
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
        } else {
            $tileContent = '';
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
