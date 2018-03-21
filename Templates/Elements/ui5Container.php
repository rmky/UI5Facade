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
    
    public function buildJsConstructor()
    {
        return $this->buildJsChildrenConstructors();
    }
    
    public function buildJsChildrenConstructors()
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $widget) {
            $js .= ($js ? ",\n" : '') . $this->getTemplate()->getElement($widget)->buildJsConstructor();
        }
        
        return $js;
    }
}
?>