<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5Tabs extends ui5Container
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Container::buildJsConstructor()
     */
    public function buildJsConstructor() : string
    {
        return $this->buildJsIconTabBar();
    }
            
    protected function buildJsIconTabBar()
    {
        return <<<JS
    new sap.m.IconTabBar("{$this->getId()}", {
        expanded: "{device>/isNoPhone}",
        /*stretchContentHeight: true,*/ // makes header of ObjectPage inivsible if set
        items: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
JS;
    }
}
?>
