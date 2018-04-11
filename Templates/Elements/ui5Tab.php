<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5Tab extends ui5Panel
{
    
    public function buildJsConstructor()
    {
        return $this->buildJsIconTabFilter();
    }
    
    protected function buildJsIconTabFilter()
    {
        $caption = str_replace('"', '\"', $this->getCaption());
        return <<<JS
    new sap.m.IconTabFilter("{$this->getId()}", {
        text: "{$caption}",
        content: [
            {$this->buildJsLayoutConstructor($this->buildJsChildrenConstructors())}
        ]
    })
JS;
    }
}
?>
