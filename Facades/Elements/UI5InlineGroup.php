<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisableConditionTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputValidationTrait;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait;

/**
 * Generates OpenUI5 inputs
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5InlineGroup extends UI5Value
{
    use JqueryContainerTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        return $this->buildJsLabelWrapper($this->buildJsConstructorForMainControl($oControllerJs));
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        new sap.m.HBox("{$this->getId()}", {
            items: [
                {$this->buildJsChildrenConstructors()}
            ]
        })
        {$this->buildJsPseudoEventHandlers()}
JS;
    }
    
    /**
     *
     * @return string
     */
    public function buildJsChildrenConstructors() : string
    {
        $js = '';
        $separatorWidgets = $this->getWidget()->getSeparatorWidgets();
        foreach ($this->getWidget()->getWidgets() as $widget) {
            if (in_array($widget, $separatorWidgets) === true) {
                $widget->setWidth('100%');
            }
            $element = $this->getFacade()->getElement($widget);
            if (in_array($widget, $separatorWidgets) === true) {
                $element->setAlignment("sap.ui.core.TextAlign.Center");
            }
            $element->setLayoutData($this->buildJsChildLayoutConstructor($element));
            
            $js .= ($js ? ",\n" : '') . $element->buildJsConstructor();
        }
        
        return $js;
    }
    
    protected function buildJsChildLayoutConstructor(UI5AbstractElement $child) : string
    {
        $props = 'growFactor: 1';
        return "new sap.m.FlexItemData({ $props })";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait::buildJsValidationError()
     */
    public function buildJsValidationError()
    {
        foreach ($this->getWidgetsToValidate() as $child) {
            $el = $this->getFacade()->getElement($child);
            $validator = $el->buildJsValidator();
            $output .= '
				if(!' . $validator . ') { ' . $el->buildJsValidationError() . '; }';
        }
        return $output;
    }
}
?>