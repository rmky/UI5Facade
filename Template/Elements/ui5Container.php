<?php
namespace exface\OpenUI5Template\Template\Elements;

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
    
    public function generateJsConstructor()
    {
        return $this->buildJsChildrenConstructors();
    }
    
    protected function buildJsChildrenConstructors()
    {
        $js = '';
        foreach ($this->getWidget()->getWidgets() as $widget) {
            $js .= ($js ? ",\n" : '') . $this->getTemplate()->getElement($widget)->generateJsConstructor();
        }
        
        return $js;
    }
}
?>