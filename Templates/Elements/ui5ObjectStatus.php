<?php
namespace exface\OpenUI5Template\Templates\Elements;

/**
 * Generates sap.m.ObjectStatus for any value widget.
 * 
 * In contrast to a regular element, ObjectStatus does not have a widget prototype. Any
 * value widget can be rendered as ObjectStatus by instantiating it manually:
 * ```
 * $element = new ui5ObjectStatus($widget, $this->getTemplate());
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class ui5ObjectStatus extends ui5Value
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\OpenUI5Template\Templates\Elements\ui5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.ObjectStatus("{$this->getId()}", {
            title: "{$this->escapeJsTextValue($this->getCaption())}",
            {$this->buildJsProperties()}
            {$this->buildJsPropertyValue()}
        })
        
JS;
    }
}
?>