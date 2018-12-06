<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5Tab extends ui5Panel
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Panel::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        // Since the tab is allways a child of Tabs, we don't need to check for hasPageWrapper() here
        return $this->buildJsIconTabFilter();
    }
    
    /**
     * 
     * @return string
     */
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