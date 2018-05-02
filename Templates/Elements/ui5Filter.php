<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFilterTrait;
use exface\Core\Widgets\InputHidden;

/**
 * Generates OpenUI5 filters
 * 
 * @method ui5AbstractElement getInputElement()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5Filter extends ui5AbstractElement
{
    use JqueryFilterTrait;
    
    public function buildJsConstructor($oController = 'oController') : string
    {
        return $this->getInputElement()->buildJsConstructor();
    }
    
    /**
     * A filter is considered not visible if it is hidden or it's input widget is an InputHidden
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5AbstractElement::isVisible()
     */
    public function isVisible()
    {
        $filter = $this->getWidget();
        if ($filter->isHidden() || $filter->getInputWidget() instanceof InputHidden) {
            return false;
        }
        return parent::isVisible();
    }
    
    public function addPseudoEventHandler($event, $code)
    {
        $this->getTemplate()->getElement($this->getWidget()->getInputWidget())->addPseudoEventHandler($event, $code);
        return $this;
    }
}
?>