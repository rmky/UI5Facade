<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\Split;

/**
 * @method Split getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Split extends ui5Container
{
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $splitter = <<<JS

    new sap.ui.layout.Splitter("{$this->getId()}", {
        height: "100%",
        width: "100%",
        orientation: "{$this->getOrientation()}",
        contentAreas: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
JS;
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($splitter);
        }
        
        return $splitter;
    }
        
    protected function getOrientation()
    {
        return $this->getWidget()->getOrientation() === Split::ORIENTATION_VERTICAL ? 'Vertical' : 'Horizontal';
    }
}
