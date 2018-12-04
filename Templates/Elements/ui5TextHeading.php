<?php
namespace exface\OpenUI5Template\Templates\Elements;

use exface\Core\Widgets\TextHeading;

/**
 * Generates sap.m.Title controls for TextHeading widgets
 * 
 * @method TextHeading getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5TextHeading extends ui5Text
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