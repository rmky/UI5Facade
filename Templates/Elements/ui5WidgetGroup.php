<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5WidgetGroup extends ui5Container
{
    
    public function buildJsConstructor($oController = 'oController') : string
    {
        $title = $this->getCaption() ? 'text: "' . $this->getCaption() . '",' : '';
        return  <<<JS
                new sap.ui.core.Title({
                    {$title}
                }),
                {$this->buildJsChildrenConstructors()}
JS;
    }
}
?>