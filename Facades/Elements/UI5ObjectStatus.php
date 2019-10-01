<?php
namespace exface\UI5Facade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iHaveColorScale;

/**
 * Generates sap.m.ObjectStatus for any value widget.
 * 
 * In contrast to a regular element, ObjectStatus does not have a widget prototype. Any
 * value widget can be rendered as ObjectStatus by instantiating it manually:
 * 
 * ```
 * $element = new UI5ObjectStatus($widget, $this->getFacade());
 * 
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5ObjectStatus extends UI5Display
{    
    private $title = null;
    
    private $inverted = false;
    
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
        
        new sap.m.ObjectStatus("{$this->getId()}", {
            title: "{$this->escapeJsTextValue($this->getTitle())}",
            {$this->buildJsProperties()}
            {$this->buildJsPropertyValue()}
            {$this->buildJsPropertyState()}
            {$this->buildJsPropertyInverted()}
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
    
    protected function buildJsPropertyState() : string
    {
        if ($this->getWidget() instanceof iHaveColorScale) {
            $stateJs = $this->buildJsColorValue();
        }
        
        return $stateJs ? 'state: ' . $stateJs . ',' : '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Display::buildJsColorValueNoColor()
     */
    protected function buildJsColorValueNoColor() : string
    {
        return 'sap.ui.core.ValueState.None';
    }
    
    /**
     *
     * @return string
     */
    protected function getTitle() : string
    {
        return $this->title ?? $this->getCaption();
    }
    
    /**
     * 
     * @param string $value
     * @return UI5ObjectStatus
     */
    public function setTitle(string $value) : UI5ObjectStatus
    {
        $this->title = $value;
        return $this;
    }
    
    protected function buildJsColorCssSetter(string $oControlJs, string $sColorJs) : string
    {
        $cssProperty = $this->getInverted() ? 'background-color' : 'color';
        return "setTimeout(function(){ $oControlJs.$().find('.sapMObjStatusText').css('$cssProperty', $sColorJs); }, 0)";
    }
    
    /**
     *
     * @return bool
     */
    protected function getInverted() : bool
    {
        return $this->inverted;
    }
    
    /**
     * 
     * @param bool $value
     * @return UI5ObjectStatus
     */
    public function setInverted(bool $value) : UI5ObjectStatus
    {
        $this->inverted = $value;
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsPropertyInverted() : string
    {
        return 'inverted: ' . ($this->getInverted() ? 'true' : 'false') . ',';
    }
}
?>