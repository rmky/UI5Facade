<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\DataSheets\DataPointerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ModelInterface;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\Core\Interfaces\Widgets\iShowDataColumn;

class UI5Model implements UI5ModelInterface
{    
    private $name = null;
    
    private $viewName = null;
    
    private $webapp = null;
    
    private $rootElement = null;
    
    private $bindings = [];
    
    /**
     * 
     * @param Webapp $webapp
     * @param string $viewName
     * @param string $modelName
     */
    public function __construct(Webapp $webapp, string $viewName, string $modelName = '')
    {
        $this->webapp = $webapp;
        $this->viewName = $viewName;
        $this->name = $modelName;
    }  
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::getName()
     */
    public function getName() : string
    {
        return $this->name;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::getViewName()
     */
    public function getViewName() : string
    {
        return $this->viewName;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::setBindingPointer()
     */
    public function setBindingPointer(WidgetInterface $widget, string $bindingName, DataPointerInterface $pointer) : UI5ModelInterface
    {
        $this->bindings[$widget->getId()][$bindingName] = $pointer;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::getBindingPath()
     */
    public function getBindingPath(WidgetInterface $widget, string $bindingName) : string
    {
        $binding = $this->bindings[$widget->getId()][$bindingName];
        
        if ($binding === null) {
            return '';
        }
        
        if ($binding instanceof DataPointerInterface) {
            return '/' . $binding->getColumn()->getName();
        }
        
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::hasBinding()
     */
    public function hasBinding(WidgetInterface $widget, string $bindingName) : bool
    {
        return $this->bindings[$widget->getId()][$bindingName] !== null;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\UI5Facade\Facades\Interfaces\UI5ModelInterface::hasBindingConflict()
     */
    public function hasBindingConflict(WidgetInterface $widget, string $bindingName) : bool
    {
        // If there is not binding for the given widget and property, check if they conflict
        // with another binding
        if (! $this->hasBinding($widget, $bindingName)) {
            // Iterat through bindings looking for those with the same binding name, but a different
            // widget id.
            foreach ($this->bindings as $widgetId => $bindings) {
                if ($bindings[$bindingName] !== null && $widgetId !== $widget->getId()) {
                    $bindingWidget = $widget->getPage()->getWidget($widgetId);
                    // If the other binding's widget or the current widget are not showing a single attribute,
                    // it's really strange - so treat that as a potential conflict :)
                    if (! ($bindingWidget instanceof iShowSingleAttribute) || ! ($widget instanceof iShowSingleAttribute)) {
                        return true;
                    }
                    // Same goes for the case, that one of the widgets is not bound to a data column
                    if (! ($bindingWidget instanceof iShowDataColumn) || ! ($widget instanceof iShowDataColumn)) {
                        return true;
                    }
                    
                    // If both can be bound to an attribute, but at least one is not, AND both have
                    // the same data column name - it's OK, the same value is meant.
                    if ((! $bindingWidget->isBoundToAttribute() || ! $widget->isBoundToAttribute()) && $bindingWidget->getDataColumnName() === $widget->getDataColumnName()) {
                        continue;
                    }
                    // If both are bound to an attribute, but the attributes are different, it's a conflict!
                    if (! $bindingWidget->getAttribute()->is($widget->getAttribute())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}