<?php
namespace exface\OpenUI5Template\Template\Elements;

class ui5WidgetGroup extends ui5Container
{
    
    public function generateJsConstructor()
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