<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\InputDateTime;

/**
 * Generates sap.m.DateTimePicker for InputDateTime widgets
 * 
 * @method InputDateTime getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class UI5InputDateTime extends UI5InputDate
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS

        new sap.m.DateTimePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
		}){$this->buildJsPseudoEventHandlers()}

JS;
    }
         
    /**
     * 
     * @return string
     */
    protected function buildJsValueFormat() : string
    {
        return '"yyyy-MM-dd HH:mm:ss"';
    }
        
    /**
     * 
     * @return string
     */
    protected function buildJsDisplayFormat() : string
    {
        return '""';
    }
    
}