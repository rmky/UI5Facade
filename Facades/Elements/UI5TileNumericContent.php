<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iHaveIcon;

/**
 * Generates sap.m.NumericContent for any display widget.
 * 
 * In contrast to a regular element, TileNumericContent does not have a widget prototype. Any
 * value widget can be rendered as TileNumericContent by instantiating it manually:
 * 
 * ```
 * $element = new UI5TileNumericContent($widget, $this->getFacade());
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5TileNumericContent extends UI5Display
{    
    private $icon = null;
    
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
        
                new sap.m.NumericContent("{$this->getId()}", {
                    {$this->buildJsPropertyIcon()}
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
    
    /**
     *
     * @return string
     */
    public function getIcon() : ?string
    {
        if ($this->icon === null && $this->getWidget() instanceof iHaveIcon) {
            return $this->getWidget()->getIcon();
        }
        return $this->icon;
    }
    
    /**
     * 
     * @param string $value
     * @return UI5TileNumericContent
     */
    public function setIcon(string $value) : UI5TileNumericContent
    {
        $this->icon = $value;
        return $this;
    }
    
    protected function buildJsPropertyIcon() : string
    {
        if ($icon = $this->getIcon()) {
            return 'icon: "' . $this->getIconSrc($icon) . '",';
        }
        
        return '';
    }
    
    protected function buildJsPropertyValue()
    {
        return <<<JS
            value: {$this->buildJsValue()},
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsValueGetterMethod()
     */
    public function buildJsValueGetterMethod()
    {
        return "getValue()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return "setValue({$value})";
    }
}
?>