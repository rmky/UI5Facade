<?php
namespace exface\OpenUI5Template\Template\Elements;

class ui5Tab extends ui5Panel
{
    
    public function generateJsConstructor()
    {
        return $this->buildJsIconTabFilter();
    }
    
    protected function buildJsIconTabFilter()
    {
        return <<<JS
    new sap.m.IconTabFilter("{$this->getId()}", {
        text: "{$this->getCaption()}",
        content: [
            {$this->buildJsLayoutConstructor($this->buildJsChildrenConstructors())}
        ]
    })
JS;
    }
}
?>
