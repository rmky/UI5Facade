<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\DataSheets\DataPointerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ModelInterface;

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
        if (! $this->hasBinding($widget, $bindingName)) {
            foreach ($this->bindings as $widgetId => $bindings) {
                if ($bindings[$bindingName] !== null && $widgetId !== $widget->getId()) {
                    return true;
                }
            }
        }
        return false;
    }
}