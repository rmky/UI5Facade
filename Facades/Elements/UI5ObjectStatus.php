<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates sap.m.ObjectStatus for any value widget.
 * 
 * In contrast to a regular element, ObjectStatus does not have a widget prototype. Any
 * value widget can be rendered as ObjectStatus by instantiating it manually:
 * 
 * ```
 * $element = new UI5ObjectStatus($widget, $this->getFacade());
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5ObjectStatus extends UI5Display
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
        $this->registerColorResolver($oControllerJs);
        return $this->buildJsConstructorForMainControl($oControllerJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.ObjectStatus("{$this->getId()}", {
            title: "{$this->escapeJsTextValue($this->getCaption())}",
            {$this->buildJsProperties()}
            {$this->buildJsPropertyValue()}
        })
        {$this->buildJsPseudoEventHandlers()}
        
JS;
    }
        
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsPropertyWidth()
     */
    protected function buildJsPropertyWidth()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsPropertyWrapping()
     */
    protected function buildJsPropertyWrapping()
    {
        return '';
    }
}
?>