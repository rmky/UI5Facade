<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryFilterTrait;
use exface\Core\Widgets\InputHidden;

/**
 * Generates OpenUI5 filters
 * 
 * @method ui5AbstractElement getInputElement()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5Filter extends UI5AbstractElement
{
    use JqueryFilterTrait;
    
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->getInputElement()->buildJsConstructor();
    }
    
    /**
     * A filter is considered not visible if it is hidden or it's input widget is an InputHidden
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::isVisible()
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
        $this->getFacade()->getElement($this->getWidget()->getInputWidget())->addPseudoEventHandler($event, $code);
        return $this;
    }
}
?>