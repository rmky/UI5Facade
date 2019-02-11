<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\InputDate;

/**
 * Generates sap.m.DatePicker for InputDate widgets
 * 
 * @method InputDate getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class ui5InputDate extends ui5Input
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS

        new sap.m.DatePicker("{$this->getId()}", {
            {$this->buildJsProperties()}
		}){$this->buildJsPseudoEventHandlers()}

JS;
    }
          
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Input::buildJsProperties()
     */
    public function buildJsProperties()
    {
        $options = parent::buildJsProperties() . <<<JS
            
			valueFormat: {$this->buildJsValueFormat()},
            displayFormat: {$this->buildJsDisplayFormat()}

JS;
            return $options;
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