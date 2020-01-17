<?php
namespace exface\UI5Facade\Facades\Elements;

/**
 * Generates sap.m.SearchField for any value widget.
 * 
 * In contrast to a regular element, SearchField does not have a widget prototype. Any
 * value widget can be rendered as ObjectStatus by instantiating it manually:
 * 
 * ```
 * $element = new UI5SearchField($widget, $this->getFacade());
 * ```
 *
 * @author Andrej Kabachnik
 *        
 */
class UI5SearchField extends UI5Value
{    
    private $placeholder = null;
    
    private $searchCallbackJs = '';
    
    private $widthCollapsed = '0px';
    
    private $widthExpanded = '200px';
  
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5Value::buildJsConstructorForMainControl()
     */
    public function buildJsConstructorForMainControl($oControllerJs = 'oController')
    {
        return <<<JS
        
        new sap.m.SearchField("{$this->getId()}", {
            width: {$this->buildJsPropertyWidthCollapsed()},
            placeholder: "{$this->getPlaceholder()}",
            search: {$this->getSearchCallbackJs()},
            layoutData: new sap.m.OverflowToolbarLayoutData({priority: "NeverOverflow"})
        }).addEventDelegate({
            onfocusin : function(oEvent) {
                oEvent.srcControl.setWidth({$this->buildJsPropertyWidthExpanded()});
            },
            onsapfocusleave : function(oEvent) {
                var oInput = oEvent.srcControl;
                if (oInput.getValue() === undefined || oInput.getValue().length < 4) {
                    oEvent.srcControl.setWidth({$this->buildJsPropertyWidthCollapsed()});
                }
            }
        }),
        
JS;
    }
        
    protected function buildJsPropertyWidthCollapsed() : string
    {
        return "'{$this->getWidthCollapsed()}'";
    }
    
    protected function buildJsPropertyWidthExpanded() : string
    {
        return "'{$this->getWidthExpanded()}'";
    }
        
    public function setPlaceholder(string $string) : UI5SearchField
    {
        $this->placeholder = $string;
        return $this;
    }
    
    protected function getPlaceholder() : string
    {
        return $this->placeholder ?? '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Elements\UI5AbstractElement::buildJsValueSetter()
     */
    public function buildJsValueSetterMethod($valueJs)
    {
        return parent::buildJsValueSetterMethod($valueJs) . '.fireSearch({query: ' . $valueJs . '})';
    }
    
    public function setSearchCallbackJs(string $js) : UI5SearchField
    {
        $this->searchCallbackJs = $js;
        return $this;
    }
    
    protected function getSearchCallbackJs() : string
    {
        return $this->searchCallbackJs;
    }
    
    public function setWidthCollapsed(string $value) : UI5SearchField
    {
        $this->widthCollapsed = $value;
        return $this;
    }
    
    protected function getWidthCollapsed() : string
    {
        return $this->widthCollapsed;
    }
    
    public function setWidthExpanded(string $value) : UI5SearchField
    {
        $this->widthExpanded = $value;
        return $this;
    }
    
    protected function getWidthExpanded() : string
    {
        return $this->widthExpanded;
    }
}