<?php
namespace exface\OpenUI5Template\Templates\Elements;

class ui5Tabs extends ui5Container
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Container::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $iconTabBar = $this->buildJsIconTabBar();
        
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($iconTabBar);
        }
        
        return $iconTabBar;
    }
            
    protected function buildJsIconTabBar()
    {
        return <<<JS
    new sap.m.IconTabBar("{$this->getId()}", {
        expanded: "{device>/isNoPhone}",
        showOverflowSelectList: true,
        /*stretchContentHeight: true,*/ // makes header of ObjectPage inivsible if set
        items: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
JS;
    }
}
?>
