<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 *
 * @method NavMenu getWidget()
 * @method UI5ControllerInterface getController()
 *
 * @author Ralf Mulansky
 *
 */
class UI5NavMenu extends UI5AbstractElement
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructorForControl($oControllerJs = 'oController') : string
    {
        $menu = $this->getWidget()->getMenu();
        
        return <<<JS

new sap.tnt.NavigationList({
    items: [{$this->buildNavigationListItems($menu)}]
});

JS;
    }
    
    /**
     * 
     * @param UiPageTreeNode[] $menu
     * @return string
     */
    protected function buildNavigationListItems(array $menu) : string
    {
        $output = '';
        foreach ($menu as $node) {
            $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
            $icon = "folder-blank";
            if ($node->isInPath() === true) {
                $icon = "open-folder";
            }
            if ($node->hasChildNodes() !== false) {
                $output = <<<JS

new sap.tnt.NavigationListItem({
    icon: "{$icon}", 
    text: "{$node->getName()}", 
    select: function(){sap.ui.core.BusyIndicator.show(0); window.location.href = '{$url}';} 
}),

JS;
            } else {
                $output = <<<JS

new sap.tnt.NavigationListItem({
    icon: "{$icon}",
    text: "{$node->getName()}",
    items: [
        // BOF {$node->getName()} SubMenu

        {$this->buildNavigationListItems($node->getChildNodes())}

        //EOF {$node->getName()} SubMenu
        ],
    select: function(){sap.ui.core.BusyIndicator.show(0); window.location.href = '{$url}';}
}),

JS;
                
            }
            
        return $output;
        }
    }
}