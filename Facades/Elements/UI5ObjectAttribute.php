<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates sap.m.ObjectAttribute for any value widget.
 * 
 * In contrast to a regular element, ObjectAttribute does not have a widget prototype. Any
 * value widget can be rendered as ObjectAttribute by instantiating it manually:
 * 
 * ```
 * $element = new UI5ObjectAttribute($widget, $this->getFacade());
 * 
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5ObjectAttribute extends UI5Display
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsConstructor()
     */
    public function buildJsConstructor($oControllerJs = 'oController') : string
    {
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
        
        new sap.m.ObjectAttribute({
            {$this->buildJsProperties()}
        })
        {$this->buildJsPseudoEventHandlers()}
        
JS;
    }
    
    public function buildJsProperties()
    {
        return <<<JS

            {$this->buildJsPropertyValue()}
            {$this->buildJsPropertyTooltip()}
            {$this->buildJsPropertyVisibile()}
            {$this->buildJsPropertyTitle()}

JS;
    }
    
    protected function buildJsPropertyTitle() : string
    {
        if ($caption = $this->getCaption()) {
            return 'title: ' . json_encode($caption) . ',';
        }
        return '';
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