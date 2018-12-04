<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;
use exface\Core\Widgets\Container;

/**
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
        if ($this->isPanel()) {
            return $this->buildJsPanelWrapper($this->buildJsChildrenConstructors());
        }
        
        return $this->buildJsChildrenConstructors();
    }
    
    protected function buildJsPanelWrapper(string $contentJs) : string
    {
        if ($caption = $this->getCaption()) {
            $heading = "headerText: '{$caption}',";
        }
        return <<<JS

        new sap.m.Panel("{$this->getId()}", {
            {$heading}
            content: [
                {$contentJs}
            ]
        }).addStyleClass("sapUiNoMargin").addStyleClass("sapUiNoContentPadding")

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
    
    protected function isPanel() : bool
    {
        return $this->getWidget()->hasParent() === false || $this->getCaption();
    }
}
?>