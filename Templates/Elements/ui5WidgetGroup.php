<?php
namespace exface\UI5Facade\Facades\Elements;

class ui5WidgetGroup extends ui5Container
{
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
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