<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputTime;

/**
 * Renders a sap.m.TimePicker for InputTime widgets.
 * 
 * @method InputTime getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class UI5InputTime extends UI5InputDate
{
    
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.TimePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
		}){$this->buildJsPseudoEventHandlers()}
		
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsValueFormat()
     */
    protected function buildJsValueFormat() : string
    {
        return '"HH:mm:ss"';
    }
            
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5InputDate::buildJsDisplayFormat()
     */
    protected function buildJsDisplayFormat() : string
    {
        $widget = $this->getWidget();
        
        $format = 'HH:mm';
        if ($widget->getShowSeconds() === true) {
            $format .= ':ss';
        }
        if ($widget->getAmPm() === true) {
            $format .= ' a';
        }
        
        return '"' . $format . '"';
    }
    
}