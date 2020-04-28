<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\Tiles;

/**
 * Generates a sap.m.Panel intended to contain tiles (see. UI5Tile).
 * 
 * @method Tiles getWiget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5Tiles extends UI5Container
{
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $tiles = $this->buildJsChildrenConstructors();
        if ($this->getWidget()->getCenterContent(false) === true) {
            $tiles = $this->buildJsCenterWrapper($tiles);
        }
        
        $panel = <<<JS

                new sap.m.Panel("{$this->getId()}", {
                    {$this->buildJsPropertyHeight()}
                    content: [
                        {$tiles}
                    ],
                    {$this->buildJsProperties()}
                })

JS;
                
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($panel);
        }
        
        return $panel;
    }
    
    protected function buildJsCenterWrapper(string $content) : string
    {
        return <<<JS
        
                        new sap.m.FlexBox({
                            height: "100%",
                            width: "100%",
                            justifyContent: "Center",
                            alignItems: "Center",
                            items: [
                                {$content}
                            ]
                        })
                        
JS;
    }
                    
    public function buildJsProperties()
    {
        return parent::buildJsProperties() . $this->buildJsPropertyHeaderText();
    }
    
    public function isStretched() : bool
    {
        $lastWidth = null;
        foreach ($this->getWidget()->getTiles() as $tile) {
            $w = $tile->getWidth();
            if ($w->isUndefined() === true || $w->isPercentual() === false || ($lastWidth !== null && $lastWidth !== $w->getValue())) {
                return false;
            } else {
                $lastWidth = $w->getValue();
            }
        }
        
        return true;
    }
                    
    protected function buildJsPropertyHeaderText() : string
    {
        if ($caption = $this->getCaption()) {
            return <<<JS

                    headerText: "{$caption}",

JS;
        }
        return '';
    }
}