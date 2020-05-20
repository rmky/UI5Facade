<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\NavCrumbs;

/**
 *
 * @method NavCrumbs getWidget()
 * @method UI5ControllerInterface getController()
 *
 * @author Ralf Mulansky
 *
 */
class UI5NavCrumbs extends UI5AbstractElement
{
    private $currentPage = null;

    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->currentPage = $this->getWidget()->getPage();
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
        $output = '';
        //add all breadcrumbs leading to leaf page
        
        foreach($menu as $node) {
            if ($node->isAncestorOf($this->currentPage)) {
                $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
                $output .= <<<JS
        new sap.m.Link({
            href: '{$url}',
            text: '{$node->getName()}'
        }),
        
JS;
                if ($node->hasChildNodes()) {
                    $output .= $this->buildBreadcrumbLinks($node->getChildNodes());
                }
                break;
            }
        }
        return $output;
    }
}