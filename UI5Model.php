<?php
namespace exface\UI5Facade;

use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\DataSheets\DataPointerInterface;
use exface\UI5Facade\Facades\Interfaces\UI5ModelInterface;

class UI5Model implements ui5ModelInterface
{    
    private $name = null;
    
    private $viewName = null;
    
    private $webapp = null;
    
    private $rootElement = null;
    
    private $bindings = [];
    
    public function __construct(Webapp $webapp, string $viewName, string $modelName = '')
    {
        $this->webapp = $webapp;
        $this->viewName = $viewName;
        $this->name = $modelName;
    }  
    
    public function getName() : string
    {
        return $this->name;
    }
    
    public function getViewName() : string
    {
        return $this->viewName;
    }
    
    
    public function setBindingPointer(WidgetInterface $widget, string $bindingName, DataPointerInterface $pointer) : ui5ModelInterface
    {
        $this->bindings[$widget->getId()][$bindingName] = $pointer;
        return $this;
    }
    
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
    
    public function hasBinding(WidgetInterface $widget, string $bindingName) : bool
    {
        return $this->bindings[$widget->getId()][$bindingName] !== null;
    }
}