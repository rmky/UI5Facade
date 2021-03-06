<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Widgets\TextHeading;

/**
 * Generates sap.m.Title controls for TextHeading widgets
 * 
 * @method TextHeading getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5TextHeading extends UI5Text
{
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.Title("{$this->getid()}", {
            level:"H2",
            {$this->buildJsProperties()}
    	})
    	
JS;
    }
            
    protected function buildJsLabelWrapper($element_constructor)
    {
        return $element_constructor;
    }
}