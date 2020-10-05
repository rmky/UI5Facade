<?php
namespace exface\UI5Facade\Facades\Elements;

class UI5Tabs extends UI5Container
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Container::buildJsConstructor()
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
        showOverflowSelectList: true,
        stretchContentHeight: true, // FIXME makes header of ObjectPage sometimes inivsible if set
        items: [
            {$this->buildJsChildrenConstructors()}
        ]
    })
    {$this->buildJsPseudoEventHandlers()}
JS;
    }
}
?>
