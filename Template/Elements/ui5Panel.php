<?php
namespace exface\OpenUI5Template\Template\Elements;

class ui5Panel extends ui5Container
{
    
    public function generateJsConstructor()
    {
        return  <<<JS
                new sap.m.Panel({
                    height: "100%",
                    content: [
                        {$this->buildJsChildrenConstructors()}
                    ],
                    {$this->buildJsInitOptions()}
                }).addStyleClass("sapUiNoContentPadding")
JS;
    }
}
?>