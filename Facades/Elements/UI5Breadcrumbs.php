<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\NavMenu;

/**
 *
 * @method Breadcrumbs getWidget()
 * @method UI5ControllerInterface getController()
 *
 * @author Ralf Mulansky
 *
 */
class UI5Breadcrumbs extends UI5AbstractElement
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $breadcrumbs = $this->getWidget()->getBreadcrumbs();
        $currentLocation = $this->getWidget()->getPage()->getName();
        
        $output = <<<JS

new sap.m.Breadcrumbs("{$this->getId()}", {
    links: [{$this->buildBreadcrumbLinks($breadcrumbs)}],
    separatorStyle: 'DoubleGreaterThan',
    currentLocationText: '{$currentLocation}'
})

JS;
        
        return $output;
    }
    
    /**
     * 
     * @param UiPageTreeNode[] $menu
     * @return string
     */
    protected function buildBreadcrumbLinks(array $menu) : string
    {
        $node = $menu[0]; 
        $output = '';
        //add all breadcrumbs leading to leaf page
        while ($node->hasChildNodes() === true) {
            $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
            
            $output .= <<<JS
    new sap.m.Link({
        href: '{$url}',
        text: '{$node->getName()}'
    }),
    
JS;
            
            $node = $node->getChildNodes()[0];
        }
        return $output;
    }
}
