<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;
use exface\Core\Widgets\Container;

/**
 * Renders a sap.m.Panel with no margins or paddings for a simple Container widget.
 * 
 * @method Container getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5Container extends ui5AbstractElement
{
    use JqueryContainerTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $js = $this->buildJsPanelWrapper($this->buildJsChildrenConstructors());
        
        if ($this->hasPageWrapper() === true) {
            return $this->buildJsPageWrapper($js);
        }
        
        return $js;
    }
    
    /**
     * Wraps any JS content in an sap.m.Panel with no margins/padding.
     * 
     * @param string $contentJs
     * @return string
     */
    protected function buildJsPanelWrapper(string $contentJs) : string
    {
        $caption = $this->getCaption();
        if ($caption && $this->hasPageWrapper() === false) {
            $heading = "headerText: '{$caption}',";
        }
        return <<<JS

        new sap.m.Panel("{$this->getId()}", {
            {$heading}
            {$this->buildJsPropertyHeight()}
            content: [
                {$contentJs}
            ]
        }).addStyleClass("sapUiNoMargin sapUiNoContentPadding")

JS;
    }
    
    /**
     * Returns height: "xxx" if required by the container control
     * 
     * @return string
     */
    protected function buildJsPropertyHeight() : string
    {
        if ($this->getWidget()->hasParent() === false) {
            return 'height: "100%",';
        }
        return '';
    }
                
    protected function hasPageWrapper() : bool
    {
        return $this->getWidget()->hasParent() === false && $this->getView()->isWebAppRoot() === false;
    }
                
    protected function buildJsPageWrapper(string $contentJs) : string
    {
        $showNavButton = $this->getView()->isWebAppRoot() ? 'false' : 'true';
        
        $caption = $this->getCaption();
        if ($caption === '' && $this->getWidget()->hasParent() === false) {
            $caption = $this->getWidget()->getPage()->getName();
        }
        
        return <<<JS
        
        new sap.m.Page({
            title: "{$caption}",
            showNavButton: {$showNavButton},
            navButtonPress: [oController.onNavBack, oController],
            content: [
                {$contentJs}
            ]
        })
        
JS;
    }
    
    /**
     * 
     * @return string
     */
    public function buildJsChildrenConstructors() : string
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $widget) {
            $js .= ($js ? ",\n" : '') . $this->getTemplate()->getElement($widget)->buildJsConstructor();
        }
        
        return $js;
    }
}