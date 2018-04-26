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
    public function buildJsConstructor() : string
    {
        return $this->buildJsChildrenConstructors();
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsControllerProperties()
     */
    public function buildJsControllerProperties() : string
    {
        $js = parent::buildJsControllerProperties();
        
        foreach ($this->getWidget()->getChildren() as $subw) {
            $js .= $this->getTemplate()->getElement($subw)->buildJsControllerProperties() . "\n";
        }
        
        return $js;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::buildJsOnInitScript()
     */
    public function buildJsOnInitScript() : string
    {
        $output = parent::buildJsOnInitScript();
        
        foreach ($this->getWidget()->getChildren() as $subw) {
            $output .= $this->getTemplate()->getElement($subw)->buildJsOnInitScript() . "\n";
        }
        
        return $output;
    }
}
?>