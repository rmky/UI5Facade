<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputTime;

/**
 * Renders a sap.m.TimePicker for InputTime widgets.
 * 
 * @method InputTime getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class ui5InputTime extends ui5InputDate
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
     * @see \exface\OpenUI5Template\Templates\Elements\ui5InputDate::buildJsValueFormat()
     */
    protected function buildJsValueFormat() : string
    {
        return '"HH:mm:ss"';
    }
            
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5InputDate::buildJsDisplayFormat()
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