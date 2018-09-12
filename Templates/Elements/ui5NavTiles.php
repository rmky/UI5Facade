<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\NavTiles;

/**
 * Generates a sap.m.Panel intended to contain tiles (see. ui5Tile)
 * 
 * @method NavTiles getWiget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5NavTiles extends ui5Container
{
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return  <<<JS
                new sap.m.Panel("{$this->getId()}", {
                    height: "100%",
                    content: [
                        {$this->buildJsChildrenConstructors()}
                    ],
                    {$this->buildJsProperties()}
                })
JS;
    }
}